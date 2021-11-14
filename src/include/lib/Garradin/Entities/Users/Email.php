<?php
declare(strict_types=1);

namespace Garradin\Entities\Users;
use Garradin\Entity;

class Email extends Entity
{
	const TABLE = 'emails';

	const COMMON_DOMAINS = ['laposte.net', 'gmail.com', 'hotmail.fr', 'hotmail.com', 'wanadoo.fr', 'free.fr', 'sfr.fr', 'yahoo.fr', 'orange.fr', 'live.fr', 'outlook.fr', 'yahoo.com', 'neuf.fr', 'outlook.com', 'icloud.com', 'riseup.net', 'vivaldi.net', 'aol.com', 'gmx.de', 'lilo.org', 'mailo.com', 'protonmail.com'];

	protected string $email;
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

		return hash('sha256', $email);
	}

	public function getVerificationCode(): string
	{
		$code = base64_encode($this->hash);
		$code = preg_replace('/[^\w\d]+/', $code);
		return substr($code, 0, 10);
	}

	public function verify(string $code): bool
	{
		if ($code !== $this->getVerificationCode()) {
			return false;
		}

		$this->verified = true;
		return true;
	}

	public function validate(): void
	{
		$user = strtok($this->email, '@');
		$host = strtok('');

		// Ce domaine n'existe pas (MX inexistant), erreur de saisie courante
		if ($host == 'gmail.fr') {
			throw new UserException('L\'adresse e-mail est invalide : est-ce que vous avez voulu écrire "gmail.com" ?');
		}

		if (!$this->isValid()) {
			foreach (self::COMMON_DOMAINS as $common_domain) {
				similar_text($common_domain, $host, $percent);

				if ($percent > 90) {
					throw new UserException(sprintf('Adresse e-mail invalide : avez-vous fait une erreur, par exemple "%s" à la place de "%s" ?', $host, $common_domain));
				}
			}

			throw new UserException('Adresse e-mail invalide : vérifiez que vous n\'avez pas fait une faute de frappe.');
		}

		if ($this->optout) {
			throw new UserException('Cette adresse e-mail a demandé à ne plus recevoir de messages de notre part.');
		}

		if ($this->invalid) {
			throw new UserException('Cette adresse e-mail est invalide.');
		}
	}

	public function isValid(): bool
	{
		return SMTP::checkEmailIsValid($this->email, true);
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

		if ($this->fail_count >= Emails::FAIL_LIMIT) {
			return false;
		}

		return true;
	}

	public function incrementSentCount(): void
	{
		$this->sent_count++;
	}

	public function setOptout(): void
	{
		$this->optout = true;
		$this->appendFailLog('Demande de désinscription');
	}

	public function appendFailLog(string $message): void
	{
		if ($this->fail_log) {
			$this->fail_log .= "\n";
		}

		$this->fail_log .= date('d/m/Y H:i:s - ') . trim($message);
	}

	public function hasFailed(array $return): void
	{
		if (!isset($return['type'])) {
			throw new \InvalidArgumentException('Bounce email type not supplied in argument.');
		}

		// Treat complaints as opt-out
		if ($return['type'] == 'complaint') {
			$this->optout = true;
			$this->appendFailLog("Un signalement de spam a été envoyé par le destinataire.\n: " . $return['message']);
		}
		elseif ($return['type'] == 'permanent') {
			$email->invalid = true;
			$email->fail_count++;
			$this->appendFailLog($return['message']);
		}
		elseif ($return['type'] == 'temporary') {
			$email->fail_count++;
			$this->appendFailLog($return['message']);
		}
	}
}
