<?php
declare(strict_types=1);

namespace Paheko\Entities\Email;

use Paheko\Entity;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\Email\Emails;
use Paheko\Email\Templates as EmailTemplates;

use KD2\SMTP;

use const Paheko\{WWW_URL, LOCAL_SECRET_KEY};

class Email extends Entity
{
	const TABLE = 'emails';

	const RESEND_VERIFICATION_DELAY = 15;

	/**
	 * Antispam services that require to do a manual action to accept emails
	 */
	const BLACKLIST_MANUAL_VALIDATION_MX = '/mailinblack\.com|spamenmoins\.com/';

	const COMMON_DOMAINS = ['laposte.net', 'gmail.com', 'hotmail.fr', 'hotmail.com', 'wanadoo.fr', 'free.fr', 'sfr.fr', 'yahoo.fr', 'orange.fr', 'live.fr', 'outlook.fr', 'yahoo.com', 'neuf.fr', 'outlook.com', 'icloud.com', 'riseup.net', 'vivaldi.net', 'aol.com', 'gmx.de', 'lilo.org', 'mailo.com', 'protonmail.com'];

	protected int $id;
	protected string $hash;
	protected bool $verified = false;
	protected bool $invalid = false;
	protected int $sent_count = 0;
	protected int $fail_count = 0;
	protected ?string $fail_log;
	protected \DateTime $added;
	protected ?\DateTime $last_sent;

	protected bool $accepts_messages = true;
	protected bool $accepts_reminders = true;
	protected bool $accepts_mailings = true;

	/**
	 * Normalize email address and create a hash from this
	 */
	static public function getHash(string $email): string
	{
		$email = strtolower(trim($email));

		$host = substr($email, strrpos($email, '@')+1);
		$host = idn_to_ascii($host);

		$email = substr($email, 0, strrpos($email, '@')+1) . $host;

		return sha1($email);
	}

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
				return $r->accepts_mailings === false ? false : true;
			case Emails::CONTEXT_REMINDER:
				return $r->accepts_reminders === false ? false : true;
			default:
				return $r->accepts_messages === false ? false : true;
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
		if (self::getHash($email) !== $this->hash) {
			throw new UserException('Adresse email inconnue');
		}

		$verify_url = self::getOptoutURL($this->hash) . '&y=' . $this->getVerificationCode();
		EmailTemplates::verifyAddress($email, $verify_url);
	}

	public function canSendVerificationAfterFail(): bool
	{
		if ($this->canSend()) {
			return false;
		}

		if (!$this->last_sent) {
			return true;
		}

		$limit_date = new \DateTime(sprintf('%d days ago', self::RESEND_VERIFICATION_DELAY));
		$date = $this->last_sent ?? $this->added;
		return $date < $limit_date;
	}

	public function verify(string $code): bool
	{
		if ($code !== $this->getVerificationCode()) {
			return false;
		}

		$this->set('verified', true);
		$this->set('invalid', false);
		$this->set('fail_count', 0);
		$this->appendFailLog('Adresse vérifiée');

		Plugins::fire('email.address.verified', false, ['address' => $this]);

		return true;
	}

	public function validate(string $email): bool
	{
		if (!$this->canSend()) {
			return false;
		}

		try {
			self::validateAddress($email);
		}
		catch (UserException $e) {
			$this->hasBounced('hard', $e->getMessage());
			return false;
		}

		return true;
	}

	static public function isAddressValid(string $email, bool $mx_check = true): bool
	{
		try {
			self::validateAddress($email);
			return true;
		}
		catch (UserException $e) {
			return false;
		}
	}

	static public function validateAddress(string $email, bool $mx_check = true): void
	{
		$pos = strrpos($email, '@');

		if ($pos === false) {
			throw new UserException('Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.');
		}

		$user = substr($email, 0, $pos);
		$host = substr($email, $pos+1);

		// Ce domaine n'existe pas (MX inexistant), erreur de saisie courante
		if ($host == 'gmail.fr') {
			throw new UserException('Adresse invalide : "gmail.fr" n\'existe pas, il faut utiliser "gmail.com"');
		}

		if (preg_match('![/@]!', $user)) {
			throw new UserException('Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.');
		}

		if (!SMTP::checkEmailIsValid($email, false)) {
			if (!trim($host)) {
				throw new UserException('Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.');
			}

			foreach (self::COMMON_DOMAINS as $common_domain) {
				similar_text($common_domain, $host, $percent);

				if ($percent > 90) {
					throw new UserException(sprintf('Adresse e-mail invalide : avez-vous fait une erreur, par exemple "%s" à la place de "%s" ?', $host, $common_domain));
				}
			}

			throw new UserException('Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.');
		}

		// Windows does not support MX lookups
		if (PHP_OS_FAMILY == 'Windows' || !$mx_check) {
			return;
		}

		self::checkMX($host);
	}

	static public function checkMX(string $host)
	{
		if (PHP_OS_FAMILY == 'Windows') {
			return;
		}

		static $results = [];

		if (array_key_exists($host, $results)) {
			$r = $results[$host];
		}
		else {
			getmxrr($host, $mx_list);
			$r = null;

			if (empty($mx_list)) {
				$r = 'empty';
			}
			else {
				foreach ($mx_list as $mx) {
					if (preg_match(self::BLACKLIST_MANUAL_VALIDATION_MX, $mx)) {
						$r = 'blocked';
						break;
					}
				}
			}

			$results[$host] = $r;
		}

		if ($r === 'empty') {
			throw new UserException('Adresse e-mail invalide (le domaine indiqué n\'a pas de service e-mail) : vérifiez que vous n\'avez pas fait une faute de frappe.');
		}
		elseif ($r === 'blocked') {
			throw new UserException('Adresse e-mail invalide : impossible d\'envoyer des mails à un service (de type mailinblack ou spamenmoins) qui demande une validation manuelle de l\'expéditeur. Merci de choisir une autre adresse e-mail.');
		}
	}

	public function canSend(): bool
	{
		if (!empty($this->invalid)) {
			return false;
		}

		if ($this->hasReachedFailLimit()) {
			return false;
		}

		return true;
	}

	public function hasReachedFailLimit(): bool
	{
		return !empty($this->fail_count) && ($this->fail_count >= Emails::FAIL_LIMIT);
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

		$log_accepts = [];
		$log_denies = [];
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

			if($this->$key) {
				$log_accepts[] = $label;
			}
			else {
				$log_denies[] = $label;
			}
		}

		$log = [];

		if (count($log_accepts)) {
			$log[] = sprintf('Accepte les messages (%s) : %s', $who, implode(', ', $log_accepts));
		}

		if (count($log_denies)) {
			$log[] = sprintf('Refuse les messages (%s) : %s', $who, implode(', ', $log_denies));
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

	public function appendFailLog(string $message): void
	{
		$log = $this->fail_log ?? '';

		if ($log) {
			$log .= "\n";
		}

		$log .= date('d/m/Y H:i:s - ') . trim($message);
		$this->set('fail_log', $log);
	}

	public function hasBounced(string $type, ?string $message = null): void
	{
		// Treat complaints as opt-out
		if ($type == 'complaint') {
			$this->set('invalid', true);
			$this->appendFailLog($message ?? "Le destinataire a signalé un message comme étant un spam.");
		}
		elseif ($type == 'hard') {
			$this->set('invalid', true);
			$this->set('fail_count', $this->fail_count+1);
			$this->appendFailLog($message);
		}
		elseif ($type == 'soft') {
			$this->set('fail_count', $this->fail_count+1);
			$this->appendFailLog($message);
		}
		else {
			throw new \LogicException('Invalid bounce type: ' . $type);
		}
	}

	public function savePreferencesFromUserForm(?array $source = null): string
	{
		$source ??= $_POST;

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

		$address = $source['email'] ?? '';

		if ($require_confirm && !empty($address)) {
			if (self::getHash($address) !== $this->hash) {
				throw new UserException('L\'adresse e-mail indiquée ne correspond pas à celle que nous avons enregistré. Merci de vérifier l\'adresse e-mail saisie.');
			}

			$url = $this->getSignedUserPreferencesURL($preferences);
			$preferences = array_filter($preferences);
			EmailTemplates::verifyPreferences($address, $url, $preferences);
			return 'confirmation_sent';
		}
		elseif ($require_confirm) {
			return 'confirmation_required';
		}
		else {
			$this->setPreferences($preferences);
			$this->save();
			return 'saved';
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

		$this->setPreferences([
			'accepts_reminders' => boolval($values['r'] ?? false),
			'accepts_mailings'  => boolval($values['l'] ?? false),
			'accepts_messages'  => boolval($values['m'] ?? false),
		]);

		$this->save();
		return true;
	}
}
