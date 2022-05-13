<?php
declare(strict_types=1);

namespace Garradin\Entities\Users;

use Garradin\Entity;
use Garradin\UserException;
use Garradin\Users\Emails;

use const Garradin\{WWW_URL, SECRET_KEY};

class Email extends Entity
{
	const TABLE = 'emails';

	/**
	 * Antispam services that require to do a manual action to accept emails
	 */
	const BLACKLIST_MANUAL_VALIDATION_MX = '/mailinblack\.com|spamenmoins\.com/';

	const COMMON_DOMAINS = ['laposte.net', 'gmail.com', 'hotmail.fr', 'hotmail.com', 'wanadoo.fr', 'free.fr', 'sfr.fr', 'yahoo.fr', 'orange.fr', 'live.fr', 'outlook.fr', 'yahoo.com', 'neuf.fr', 'outlook.com', 'icloud.com', 'riseup.net', 'vivaldi.net', 'aol.com', 'gmx.de', 'lilo.org', 'mailo.com', 'protonmail.com'];

	protected int $id;
	protected string $hash;
	protected bool $verified;
	protected bool $optout;
	protected bool $invalid;
	protected int $sent_count;
	protected int $fail_count;
	protected ?string $fail_log;
	protected \DateTime $added;

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
		$code = sha1($this->hash . SECRET_KEY);
		return substr($code, 0, 10);
	}

	public function sendVerification(string $email): void
	{
		if (self::getHash($email) !== $this->hash) {
			throw new UserException('Adresse email inconnue');
		}

		$message = "Bonjour,\n\nPour vérifier votre adresse e-mail pour notre association,\ncliquez sur le lien ci-dessous :\n\n";
		$message.= self::getOptoutURL($this->hash) . '&v=' . $this->getVerificationCode();
		$message.= "\n\nSi vous n'avez pas demandé à recevoir ce message, ignorez-le.";

		Emails::queue(Emails::CONTEXT_SYSTEM, [$email], null, 'Confirmez votre adresse e-mail', $message);
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
		return true;
	}

	static public function validate(string $email): void
	{
		$user = strtok($email, '@');
		$host = strtok('');

		// Ce domaine n'existe pas (MX inexistant), erreur de saisie courante
		if ($host == 'gmail.fr') {
			throw new UserException('L\'adresse e-mail est invalide : est-ce que vous avez voulu écrire "gmail.com" ?');
		}

		if (!SMTP::checkEmailIsValid($email, false)) {
			foreach (self::COMMON_DOMAINS as $common_domain) {
				similar_text($common_domain, $host, $percent);

				if ($percent > 90) {
					throw new UserException(sprintf('Adresse e-mail invalide : avez-vous fait une erreur, par exemple "%s" à la place de "%s" ?', $host, $common_domain));
				}
			}

			throw new UserException('Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.');
		}

		getmxrr($host, $mx_list);

		if (!count($mx_list)) {
			throw new UserException('Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.');
		}

		$mx_list = array_filter($mx_list,
  			fn ($mx) => !preg_match(self::BLACKLIST_MANUAL_VALIDATION_MX, $mx)
  		);

		if (!count($mx_list)) {
			throw new UserException('Adresse e-mail invalide : impossible d\'envoyer des mails à un service (de type mailinblack ou spamenmoins) qui demande une validation manuelle de l\'expéditeur. Merci de choisir une autre adresse e-mail.');
		}
	}

	public function canSend(): bool
	{
		if (!$this->verified) {
			return false;
		}

		if ($this->optout) {
			return false;
		}

		if ($this->invalid) {
			return false;
		}

		if ($this->hasReachedFailLimit()) {
			return false;
		}

		return true;
	}

	public function hasReachedFailLimit(): bool
	{
		return ($this->fail_count >= Emails::FAIL_LIMIT);
	}

	public function incrementSentCount(): void
	{
		$this->set('sent_count', $this->sent_count+1);
	}

	public function setOptout(): void
	{
		$this->set('optout', true);
		$this->appendFailLog('Demande de désinscription');
	}

	public function appendFailLog(string $message): void
	{
		$log = $this->fail_log;

		if ($this->fail_log) {
			$log .= "\n";
		}

		$log .= date('d/m/Y H:i:s - ') . trim($message);
		$this->set('fail_log', $log);
	}

	public function hasFailed(array $return): void
	{
		if (!isset($return['type'])) {
			throw new \InvalidArgumentException('Bounce email type not supplied in argument.');
		}

		// Treat complaints as opt-out
		if ($return['type'] == 'complaint') {
			$this->set('optout', true);
			$this->appendFailLog("Un signalement de spam a été envoyé par le destinataire.\n: " . $return['message']);
		}
		elseif ($return['type'] == 'permanent') {
			$this->set('invalid', true);
			$this->set('fail_count', $this->fail_count+1);
			$this->appendFailLog($return['message']);
		}
		elseif ($return['type'] == 'temporary') {
			$this->set('fail_count', $this->fail_count+1);
			$this->appendFailLog($return['message']);
		}
	}
}
