<?php
declare(strict_types=1);

namespace Paheko\Entities\Email;

use Paheko\Entity;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\Email\Emails;
use Paheko\Email\Templates as EmailsTemplates;

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
	protected bool $accepts_mailings = false;

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
		if (!$r->invalid
			|| $r->fail_count >= Emails::FAIL_LIMIT) {
			return false;
		}

		switch ($r->context) {
			case Emails::CONTEXT_BULK:
				return $r->accepts_mailings;
			case Email::CONTEXT_REMINDER;
				return $r->accepts_reminders;
			default:
				return $r->accepts_messages;
		}
	}

	public function getUserPreferencesURL()
	{
		return self::getOptoutURL($this->hash) . '&p=1';
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

		$verify_url = self::getOptoutURL($this->hash) . '&v=' . $this->getVerificationCode();
		EmailsTemplates::verifyAddress($email, $verify_url);
	}

	public function canSendVerificationAfterFail(): bool
	{
		if ($this->canSend()) {
			return false;
		}

		$limit_date = new \DateTime(sprintf('%d days ago', self::RESEND_VERIFICATION_DELAY));
		$date = $this->last_sent ?? $this->added;
		return $date < $limit_date;
	}

	public function verify(string $code, bool $accepts_mailings = false): bool
	{
		if ($code !== $this->getVerificationCode()) {
			return false;
		}

		$this->set('verified', true);
		$this->set('accepts_messages', true);
		$this->set('accepts_reminders', true);
		$this->set('accepts_mailings', $accepts_mailings);
		$this->set('invalid', false);
		$this->set('fail_count', 0);
		$this->set('fail_log', null);

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
			if (!isset($source[$name])) {
				continue;
			}

			// Don't allow the admin to re-subscribe a user
			if (!$this->$name) {
				unset($source[$name]);
				continue;
			}
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
			if (!isset($preferences[$key . '_present'])) {
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

	public function setOptout(?int $context = null): void
	{
		if ($context === Emails::CONTEXT_BULK) {
			$this->setPreferences(['accepts_mailings' => false]);
		}
		elseif ($context === Emails::CONTEXT_REMINDER) {
			$this->setPreferences(['accepts_reminders' => false]);
		}
		else {
			$this->setPreferences(['accepts_messages' => false]);
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
			$this->set('accepts_mailings', false);
			$this->set('accepts_reminders', false);
			$this->appendFailLog($message ?? "Un signalement de spam a été envoyé par le destinataire, il a été désinscrit des rappels et messages collectifs.");
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
}
