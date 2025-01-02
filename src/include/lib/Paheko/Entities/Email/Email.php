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

	const RESEND_VERIFICATION_DELAY = '15 days ago';
	const RESEND_VERIFICATION_DELAY_OPTOUT = '3 days ago';

	/**
	 * Antispam services that require to do a manual action to accept emails
	 */
	const BLACKLIST_MANUAL_VALIDATION_MX = '/mailinblack\.com|spamenmoins\.com/';

	const COMMON_DOMAINS = ['laposte.net', 'gmail.com', 'hotmail.fr', 'hotmail.com', 'wanadoo.fr', 'free.fr', 'sfr.fr', 'yahoo.fr', 'orange.fr', 'live.fr', 'outlook.fr', 'yahoo.com', 'neuf.fr', 'outlook.com', 'icloud.com', 'riseup.net', 'vivaldi.net', 'aol.com', 'gmx.de', 'lilo.org', 'mailo.com', 'protonmail.com'];

	protected int $id;
	protected string $hash;
	protected bool $verified = false;
	protected bool $optout = false;
	protected bool $invalid = false;
	protected int $sent_count = 0;
	protected int $fail_count = 0;
	protected ?string $fail_log;
	protected \DateTime $added;
	protected ?\DateTime $last_sent;

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

	static public function getOptoutURL(string $hash = null): string
	{
		$hash = hex2bin($hash);
		$hash = base64_encode($hash);
		// Make base64 hash valid for URLs
		$hash = rtrim(strtr($hash, '+/', '-_'), '=');
		return sprintf('%s?un=%s', WWW_URL, $hash);
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
		$limit_date = new \DateTime($this->optout ? self::RESEND_VERIFICATION_DELAY_OPTOUT : self::RESEND_VERIFICATION_DELAY);
		$date = $this->last_sent ?? $this->added;
		return $date < $limit_date;
	}

	public function verify(string $code): bool
	{
		if ($code !== $this->getVerificationCode()) {
			return false;
		}

		$this->set('verified', true);
		$this->set('optout', false);
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
		if (!empty($this->optout)) {
			return false;
		}

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

	public function setOptout(string $message = null): void
	{
		$this->set('optout', true);
		$this->appendFailLog($message ?? 'Demande de désinscription');
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

	public function hasBounced(string $type, string $message = null): void
	{
		// Treat complaints as opt-out
		if ($type == 'complaint') {
			$this->set('optout', true);
			$this->appendFailLog($message ?? "Un signalement de spam a été envoyé par le destinataire.");
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
