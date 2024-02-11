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

	const RESEND_VERIFICATION_DELAY_INVALID = 7;
	const RESEND_VERIFICATION_DELAY_OPTOUT = 2;

	/**
	 * When we reach that number of fails, the address is treated as permanently invalid, unless reset by a verification.
	 */
	const FAIL_LIMIT = 5;


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

	public function getVerificationDelay(): int
	{
		return $this->optout ? self::RESEND_VERIFICATION_DELAY_OPTOUT : self::RESEND_VERIFICATION_DELAY_INVALID;
	}

	public function canSendVerificationAfterFail(): bool
	{
		$delay = $this->getVerificationDelay() . ' days ago';
		$limit_date = new \DateTime($delay);
		return isset($this->last_sent) ? $this->last_sent < $limit_date : false;
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
		return true;
	}

	public function setAddress(string $address): bool
	{
		$this->set('added', new \DateTime);
		$this->set('hash', Addresses::hash($address));

		$error = Addresses::checkForErrors($address);

		if (null !== $error) {
			$this->setFailedValidation($error);
		}

		return $error === null;
	}

	public function setFailedValidation(string $message): void
	{
		$this->hasFailed(['type' => 'permanent', 'message' => $message]);
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
		return !empty($this->fail_count) && ($this->fail_count >= self::FAIL_LIMIT);
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
}
