<?php
declare(strict_types=1);

namespace Paheko\Entities\Email;

use Paheko\Entity;
use Paheko\UserException;
use Paheko\Email\Addresses;
use Paheko\Email\Templates as EmailsTemplates;

use KD2\SMTP;

use const Paheko\{WWW_URL, SECRET_KEY};

class Address extends Entity
{
	const TABLE = 'emails_addresses';

	const RESEND_VERIFICATION_DELAY = 24;

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
	const STATUS_SPAM = -5;

	const STATUS_LIST = [
		self::STATUS_UNKNOWN => 'OK',
		self::STATUS_VERIFIED => 'Vérifiée',
		self::STATUS_INVALID => 'Invalide',
		self::STATUS_SOFT_BOUNCE_LIMIT_REACHED => 'Trop d\'erreurs',
		self::STATUS_HARD_BOUNCE => 'Échec',
		self::STATUS_OPTOUT => 'Refus',
		self::STATUS_SPAM => 'Spam',
	];

	const STATUS_COLORS = [
		self::STATUS_UNKNOWN => 'steelblue',
		self::STATUS_VERIFIED => 'darkgreen',
		self::STATUS_INVALID => 'crimson',
		self::STATUS_SOFT_BOUNCE_LIMIT_REACHED => 'darkorange',
		self::STATUS_HARD_BOUNCE => 'darkred',
		self::STATUS_OPTOUT => 'palevioletred',
		self::STATUS_SPAM => 'darkmagenta',
	];

	protected int $id;
	protected string $hash;
	protected int $status = self::STATUS_UNKNOWN;
	protected int $sent_count = 0;
	protected int $bounce_count = 0;
	protected ?string $log;
	protected \DateTime $added;
	protected ?\DateTime $last_sent;

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
		if (Addresses::hash($email) !== $this->hash) {
			throw new UserException('Adresse email inconnue');
		}

		$verify_url = self::getOptoutURL($this->hash) . '&v=' . $this->getVerificationCode();
		EmailsTemplates::verifyAddress($email, $verify_url);
	}

	public function canSendVerificationAfterFail(): bool
	{
		$limit_date = Addresses::getVerificationLimitDate();
		return isset($this->last_sent) ? $this->last_sent < $limit_date : false;
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

	public function setOptout(string $message = null): void
	{
		$this->set('status', self::STATUS_OPTOUT);
		$this->log($message ?? 'Demande de désinscription');
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

	public function hasFailed(array $return): void
	{
		if (!isset($return['type'])) {
			throw new \InvalidArgumentException('Bounce email type not supplied in argument.');
		}

		// Treat complaints as opt-out
		if ($return['type'] == 'complaint') {
			$this->set('status', self::STATUS_SPAM);
			$this->log("Un signalement de spam a été envoyé par le destinataire.\n: " . $return['message']);
		}
		elseif ($return['type'] == 'permanent') {
			$this->set('status', self::STATUS_HARD_BOUNCE);
			$this->set('bounce_count', $this->bounce_count+1);
			$this->log($return['message']);
		}
		elseif ($return['type'] == 'temporary') {
			$this->set('bounce_count', $this->bounce_count+1);
			$this->log($return['message']);

			if ($this->bounce_count > self::SOFT_BOUNCE_LIMIT) {
				$this->set('status', self::STATUS_SOFT_BOUNCE_LIMIT_REACHED);
			}
		}
	}

	public function save(bool $selfcheck = true): bool
	{
		$optout = false;

		if ($this->isModified('optout')) {
			$optout = true;
		}

		$return = parent::save($selfcheck);

		if ($return && $optout) {
			// Delete all specific optouts when opting out of everything
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
}
