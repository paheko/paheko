<?php
declare(strict_types=1);

namespace Paheko\Entities\Email;

use Paheko\Entity;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\Email\Addresses;
use Paheko\Email\Templates as EmailsTemplates;

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

	const STATUS_UNKNOWN = null;
	const STATUS_VERIFIED = 'verified';
	const STATUS_INVALID = 'invalid';
	const STATUS_SOFT_BOUNCE_LIMIT_REACHED = 'too_many_soft_bounces';
	const STATUS_HARD_BOUNCE = 'bounce';
	const STATUS_SPAM = 'spam'; // Spam feedback (FBL)
	const STATUS_ABUSE = 'abuse'; // Never wanted to receive this message, has filed an abuse complaint

	const STATUS_LIST = [
		self::STATUS_UNKNOWN => 'OK',
		self::STATUS_VERIFIED => 'Vérifiée',
		self::STATUS_INVALID => 'Invalide',
		self::STATUS_SOFT_BOUNCE_LIMIT_REACHED => 'Trop d\'erreurs',
		self::STATUS_HARD_BOUNCE => 'Échec',
		self::STATUS_SPAM => 'Spam',
		self::STATUS_ABUSE => 'Plainte',
	];

	const STATUS_COLORS = [
		self::STATUS_UNKNOWN => 'steelblue',
		self::STATUS_VERIFIED => 'darkgreen',
		self::STATUS_INVALID => 'crimson',
		self::STATUS_SOFT_BOUNCE_LIMIT_REACHED => 'darkorange',
		self::STATUS_HARD_BOUNCE => 'darkred',
		self::STATUS_OPTOUT => 'palevioletred',
		self::STATUS_ABUSE => 'darkmagenta',
	];

	protected int $id;
	protected string $hash;
	protected ?string $status = self::STATUS_UNKNOWN;
	protected int $sent_count = 0;
	protected int $bounce_count = 0;
	protected bool $accepts_messages = true;
	protected bool $accepts_reminders = true;
	protected bool $accepts_mailings = false;
	protected \DateTime $added;
	protected ?\DateTime $last_sent;

	static public function getOptoutURL(?string $hash = null): string
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
		if (Addresses::hash($email) !== $this->hash) {
			throw new UserException('Adresse email inconnue');
		}

		$verify_url = self::getOptoutURL($this->hash) . '&v=' . $this->getVerificationCode();
		EmailsTemplates::verifyAddress($email, $verify_url);
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
		if ($this->status === self::STATUS_UNKNOWN
			|| $this->status === self::STATUS_VERIFIED) {
			return true;
		}

		return false;
	}

	public function incrementSentCount(): void
	{
		$this->set('sent_count', $this->sent_count+1);
	}

	public function setOptout(?string $message = null): void
	{
		$this->set('status', self::STATUS_OPTOUT);
		$this->log($message ?? 'Demande de désinscription');
		$this->set('accepts_messages', false);
		$this->set('accepts_reminders', false);
		$this->set('accepts_mailings', false);
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
			$this->set('status', self::STATUS_SPAM);
			$this->appendFailLog($message ?? "Un signalement de spam a été envoyé par le destinataire.");
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

	public function getStatusColor(): string
	{
		return self::STATUS_COLORS[$this->status];
	}

	public function getStatusLabel(): string
	{
		return self::STATUS_LIST[$this->status];
	}
}
