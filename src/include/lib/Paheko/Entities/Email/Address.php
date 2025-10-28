<?php
declare(strict_types=1);

namespace Paheko\Entities\Email;

use Paheko\Entity;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\Email\Addresses;
use Paheko\Email\Templates as EmailTemplates;

use KD2\SMTP;

use const Paheko\{WWW_URL, LOCAL_SECRET_KEY};

class Address extends Entity
{
	const TABLE = 'emails_addresses';

	const RESEND_VERIFICATION_DELAY = 15;

	/**
	 * When we reach that number of fails, the address is treated as permanently invalid, unless reset by a verification.
	 */
	const SOFT_BOUNCE_LIMIT = 5;

	const STATUS_UNKNOWN = 0;
	const STATUS_VERIFIED = 1;
	const STATUS_INVALID = -1;
	const STATUS_SOFT_BOUNCE_LIMIT_REACHED = -2;
	const STATUS_HARD_BOUNCE = -3;
	const STATUS_OPTOUT = -4;
	const STATUS_COMPLAINT = -5;

	const STATUS_LIST = [
		self::STATUS_UNKNOWN => 'OK',
		self::STATUS_VERIFIED => 'Vérifiée',
		self::STATUS_INVALID => 'Invalide',
		self::STATUS_SOFT_BOUNCE_LIMIT_REACHED => 'Trop d\'erreurs',
		self::STATUS_HARD_BOUNCE => 'Échec',
		self::STATUS_OPTOUT => 'Refus',
		self::STATUS_COMPLAINT => 'Plainte',
	];

	const STATUS_COLORS = [
		self::STATUS_UNKNOWN => 'steelblue',
		self::STATUS_VERIFIED => 'darkgreen',
		self::STATUS_INVALID => 'crimson',
		self::STATUS_SOFT_BOUNCE_LIMIT_REACHED => 'darkorange',
		self::STATUS_HARD_BOUNCE => 'darkred',
		self::STATUS_OPTOUT => 'palevioletred',
		self::STATUS_COMPLAINT => 'darkmagenta',
	];

	protected int $id;
	protected string $hash;
	protected int $status = self::STATUS_UNKNOWN;
	protected int $sent_count = 0;
	protected int $bounce_count = 0;
	protected bool $accepts_messages = true;
	protected bool $accepts_reminders = true;
	protected bool $accepts_mailings = true;
	protected \DateTime $added;
	protected ?\DateTime $last_sent;

	static public function getOptoutURL(?string $hash = null, ?int $context = null): string
	{
		$hash = hex2bin($hash);
		$hash = base64_encode($hash);
		// Make base64 hash valid for URLs
		$hash = rtrim(strtr($hash, '+/', '-_'), '=');
		$url = sprintf('%s?un=%s', WWW_URL, $hash);

		if ($context !== null) {
			$url .= '&c=' . $context;
		}

		return $url;
	}

	static public function acceptsThisMessage(\stdClass $r)
	{
		// We allow system emails to be sent to any address, even if it is invalid
		if ($r->context === Emails::CONTEXT_SYSTEM) {
			return true;
		}

		// Never send to invalid or bounced recipients
		if ($r->invalid
			|| $r->fail_count >= Emails::FAIL_LIMIT) {
			return false;
		}

		switch ($r->context) {
			case Emails::CONTEXT_BULK:
				return $r->accepts_mailings;
			case Emails::CONTEXT_REMINDER;
				return $r->accepts_reminders;
			default:
				return $r->accepts_messages;
		}
	}

	public function getUserPreferencesURL()
	{
		return self::getOptoutURL($this->hash) . '&h=1';
	}

	public function getVerificationCode(): string
	{
		$code = sha1($this->hash . LOCAL_SECRET_KEY);
		return substr($code, 0, 10);
	}

	public function sendVerification(string $email): void
	{
		if (Addresses::hash($email) !== $this->hash) {
			throw new UserException('Adresse email inconnue');
		}

		$verify_url = self::getOptoutURL($this->hash) . '&y=' . $this->getVerificationCode();
		EmailTemplates::verifyAddress($email, $verify_url);
	}

	public function canSendVerificationAfterFail(): bool
	{
		$limit_date = Addresses::getVerificationLimitDate();
		$date = $this->last_sent ?? $this->added;
		return $date < $limit_date;
	}

	public function verify(string $code): bool
	{
		if ($code !== $this->getVerificationCode()) {
			return false;
		}

		$this->set('status', self::STATUS_VERIFIED);
		$this->set('bounce_count', 0);
		$this->log('Adresse vérifiée par le destinataire');
		return true;
	}

	public function setAddress(string $address): bool
	{
		$this->set('added', new \DateTime);
		$this->set('hash', Addresses::hash($address));

		$error = Addresses::checkForErrors($address);

		if (null !== $error) {
			$this->set('status', self::STATUS_INVALID);
			$this->log($error);
		}

		return $error === null;
	}

	public function canSend(): bool
	{
		if ($this->status < self::STATUS_UNKNOWN) {
			return false;
		}

		return true;
	}

	public function incrementSentCount(): void
	{
		$this->set('sent_count', $this->sent_count+1);
	}

	public function adminSetPreferences(?array $source = null)
	{
		$source ??= $_POST;

		$keys = ['accepts_messages', 'accepts_reminders', 'accepts_mailings'];

		foreach ($keys as $name) {
			if (!array_key_exists($name . '_present', $source)) {
				continue;
			}

			// Don't allow the admin to re-subscribe a user
			if (!$this->$name) {
				unset($source[$name]);
				continue;
			}

			$source[$name] ??= false;
		}

		$this->setPreferences($source, 'administrateur');
	}

	protected function setPreferences(array $preferences, ?string $who = null): void
	{
		$options = [
			'accepts_messages'  => 'messages personnels',
			'accepts_reminders' => 'rappels',
			'accepts_mailings'  => 'messages collectifs',
		];

		$log = [];
		$who ??= 'destinataire';

		foreach ($options as $key => $label) {
			if (!array_key_exists($key, $preferences)) {
				continue;
			}

			$preferences[$key] ??= false;

			if ((bool) $preferences[$key] === $this->$key) {
				continue;
			}

			$this->set($key, (bool)$preferences[$key]);
			$log[] = sprintf('%s (%s) : %s', $this->$key ? 'Accepte les messages' : 'Refus des messages', $who, $label);
		}


		if (!count($log)) {
			return;
		}

		$this->appendFailLog(implode(" ; ", $log));
	}

	public function setOptout(int $context): void
	{
		if ($context === Emails::CONTEXT_BULK) {
			$this->set('accepts_mailings', false);
		}
		elseif ($context === Emails::CONTEXT_REMINDER) {
			$this->set('accepts_reminders', false);
		}
		else {
			$this->set('accepts_messages', false);
		}
	}

	public function log(string $message): void
	{
		$log = $this->log ?? '';

		if ($log) {
			$log .= "\n";
		}

		$log .= date('d/m/Y H:i:s - ') . trim($message);
		$this->set('log', $log);
	}

	public function hasBounced(string $type, ?string $message = null): void
	{
		// Treat complaints as opt-out
		if ($type == 'complaint') {
			$this->set('status', self::STATUS_COMPLAINT);
			$this->appendFailLog($message ?? "Un signalement de spam a été envoyé par le destinataire.");
			$this->set('accepts_mailings', false);
			$this->set('accepts_reminders', false);
			$this->appendFailLog($message ?? "Un signalement de spam a été envoyé par le destinataire, il a été désinscrit des rappels et messages collectifs.");
		}
		elseif ($type == 'hard') {
			$this->set('status', self::STATUS_HARD_BOUNCE);
			$this->set('bounce_count', $this->bounce_count+1);
			$this->log($message);
		}
		elseif ($type == 'soft') {
			$this->set('bounce_count', $this->bounce_count+1);
			$this->log($message);

			if ($this->bounce_count > self::SOFT_BOUNCE_LIMIT) {
				$this->set('status', self::STATUS_SOFT_BOUNCE_LIMIT_REACHED);
			}
		}
		else {
			throw new \LogicException('Invalid bounce type: ' . $type);
		}
	}

	public function save(bool $selfcheck = true): bool
	{
		$optout = false;

		if ($this->isModified('accepts_mailings') && !$this->accepts_mailings) {
			$optout = true;
		}

		$return = parent::save($selfcheck);

		if ($return && $optout) {
			// Delete all specific optouts when opting out of mailings
			DB::getInstance()->preparedQuery('DELETE FROM mailings_optouts WHERE email_hash = ?;', $this->hash);
		}

		return $return;
	}

	public function getStatusColor(): string
	{
		return self::STATUS_COLORS[$this->status];
	}

	public function getStatusLabel(): string
	{
		return self::STATUS_LIST[$this->status];
	}

	public function savePreferencesFromUserForm(?array $source = null, ?int $optout_context = null): bool
	{
		$source ??= $_POST;

		if (!$optout_context) {
			$address = $source['email'] ?? '';

			if (!$address || self::getHash($address) !== $this->hash) {
				throw new UserException('L\'adresse e-mail indiquée ne correspond pas à celle que nous avons enregistré. Merci de vérifier l\'adresse e-mail saisie.');
			}
		}

		$keys = ['reminders', 'messages', 'mailings'];
		$preferences = [];
		$require_confirm = false;

		foreach ($keys as $key) {
			$name = 'accepts_' . $key;
			$value = boolval($source[$name] ?? false);

			// Require double opt-in if re-subscribing to an unsubscribed item
			if ($value !== $this->$name
				&& $value === true
				&& $this->$name === false) {
				$require_confirm = true;
			}

			$preferences[$name] = $value;
		}

		// Don't require double opt-in if the user is coming from the optout link
		// at the bottom of a message
		if ($require_confirm && !$optout_context) {
			$url = $this->getSignedUserPreferencesURL($preferences);
			$preferences = array_filter($preferences);
			EmailTemplates::verifyPreferences($address, $url, $preferences);
			return false;
		}
		else {
			$this->setPreferences($preferences);
			$this->save();
			return true;
		}
	}

	public function getSignedUserPreferencesURL(array $preferences): string
	{
		$expiry = time() + 24*3600;
		$values = [
			'r' => intval($preferences['accepts_reminders'] ?? 0),
			'l' => intval($preferences['accepts_mailings'] ?? 0),
			'm' => intval($preferences['accepts_messages'] ?? 0),
			'e' => $expiry,
		];

		ksort($values);
		$values = http_build_query($values);
		$hash = hash_hmac('sha1', $values . $this->hash, LOCAL_SECRET_KEY);
		$values .= '&v=' . $hash;

		$url = self::getOptoutURL($this->hash);
		$url .= '&' . $values;
		return $url;
	}

	public function confirmPreferences(array $qs): bool
	{
		if (!isset($qs['v'], $qs['e'])) {
			return false;
		}

		$values = array_intersect_key($qs, array_flip(['r', 'l', 'm', 'e']));
		ksort($values);

		$hash = hash_hmac('sha1', http_build_query($values) . $this->hash, LOCAL_SECRET_KEY);

		if ($hash !== $qs['v']) {
			return false;
		}

		if ($qs['e'] < time()) {
			return false;
		}

		$this->set('accepts_reminders', boolval($values['r'] ?? false));
		$this->set('accepts_mailings', boolval($values['l'] ?? false));
		$this->set('accepts_messages', boolval($values['m'] ?? false));
		$this->save();
		return true;
	}
}
