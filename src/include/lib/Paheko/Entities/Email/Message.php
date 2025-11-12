<?php

namespace Paheko\Entities\Email;

use Paheko\Config;
use Paheko\Entity;

use const Paheko\{DISABLE_EMAIL, MAIL_RETURN_PATH, MAIL_SENDER};
use const Paheko\{SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_SECURITY, SMTP_HELO_HOSTNAME};

use KD2\SMTP;
use KD2\Security;
use KD2\Mail_Message;

use DateTime;

class Message extends Entity
{
	const TABLE = 'emails_queue';

	protected ?int $id = null;
	protected ?int $id_mailing = null;

	protected int $context;
	protected int $status = self::WAITING;

	protected DateTime $added;
	protected ?DateTime $modified = null;

	protected ?string $sender = null;
	protected ?string $reply_to = null;

	protected string $recipient;
	protected ?string $recipient_pgp_key = null;
	protected int $id_recipient;
	protected ?int $id_user = null;

	protected string $subject;
	protected string $body;
	protected ?string $body_html = null;
	protected ?string $headers = null;

	const STATUS_WAITING = 0;
	const STATUS_SENDING = 1;
	const STATUS_SENT = 2;
	const STATUS_FAIL = 3;

	const STATUS_LIST = [
		self::STATUS_WAITING => 'En attente',
		self::STATUS_SENDING => 'Envoi en cours',
		self::STATUS_SENT    => 'Envoyé',
		self::STATUS_FAIL    => 'Échec',
	];

	const STATUS_COLORS = [
		self::STATUS_WAITING => 'cadetblue',
		self::STATUS_SENDING => 'chocolate',
		self::STATUS_SENT    => 'darkgreen',
		self::STATUS_FAIL    => 'darkred',
	];

	const CONTEXT_SYSTEM = 0;
	const CONTEXT_BULK = 1;
	const CONTEXT_PRIVATE = 2;
	const CONTEXT_NOTIFICATION = 3;
	const CONTEXT_REMINDER = 4;

	const CONTEXT_LIST = [
		self::CONTEXT_SYSTEM => 'Système',
		self::CONTEXT_BULK => 'Collectif',
		self::CONTEXT_PRIVATE => 'Privé',
		self::CONTEXT_NOTIFICATION => 'Notification',
		self::CONTEXT_REMINDER => 'Rappel',
	];

	public function selfCheck(): void
	{
		$this->assert(in_array($this->context, self::CONTEXT_LIST), 'Contexte inconnu');
		$this->assert(in_array($this->status, self::STATUS_LIST), 'Statut inconnu');
		$this->assert(strlen($this->subject), 'Sujet vide');
		$this->assert(strlen($this->body), 'Corps vide');
		$this->assert(strlen($this->recipient), 'Destinataire absent');
		$this->assert(strlen($this->recipient_hash) === 40, 'Hash invalide');
	}

	public function setBodyFromUserTemplate(UserTemplate $template, array $data = [], bool $markdown = false): void
	{
		// Replace placeholders: {{$name}}, etc.
		$template->assignArray((array) $data, null, false);

		// Disable HTML escaping for plaintext emails
		$template->setEscapeDefault(null);
		$this->body = $template->fetch();

		if ($markdown) {
			$this->markdownToHTML();
		}
	}

	public function setHTMLBodyFromUserTemplate(UserTemplate $template, array $data = []): void
	{
		// Replace placeholders: {{$name}}, etc.
		$template->assignArray((array) $data, null, false);

		// Disable HTML escaping for plaintext emails
		$template->setEscapeDefault(null);
		$this->html_body = $template->fetch();
	}

	public function markdownToHTML(): void
	{
		$this->body_html = Render::render(Render::FORMAT_MARKDOWN, null, $this->body);
	}

	public function wrapHTML(): ?string
	{
		if ($this->context === self::CONTEXT_SYSTEM) {
			return null;
		}

		if (null === self::$main_tpl) {
			self::$main_tpl = new UserTemplate('web/email.html');
		}

		// Wrap HTML content in the email skeleton
		$main_tpl->assignArray([
			'html'    => $this->body_html,
			'address' => $this->recipient,
			'context' => $this->context,
			'sender'  => $this->sender,
			'message' => $this,
		]);

		return $main_tpl->fetch();
	}

	static public function getOptoutText(): string
	{
		return "Vous recevez ce message car vous êtes dans nos contacts.\n"
			. "Pour ne plus recevoir ces messages cliquez ici :\n";
	}

	/**
	 * @see https://www.nngroup.com/articles/unsubscribe-mistakes/
	 */
	static public function appendHTMLOptoutFooter(string $html, string $url): string
	{
		$footer = '<p style="color: #666; background: #fff; padding: 10px; margin: 50px auto 0 auto; max-width: 700px; border-top: 1px solid #ccc; text-align: center; font-size: 9pt">' . nl2br(htmlspecialchars(trim(self::getOptoutText())));
		$footer .= sprintf('<br /><a href="%s" style="color: #009; text-decoration: underline;">Me désinscrire</a></p>', $url);

		if (stripos($html, '</body>') !== false) {
			$html = str_ireplace('</body>', $footer . '</body>', $html);
		}
		else {
			$html .= $footer;
		}

		return $html;
	}

	public function getOptoutURL(): string
	{
		return Email::getOptoutURL($this->recipient_hash);
	}

	public function getContextSpecificOptoutURL(): ?string
	{
		if (!isset($this->context_optout)) {
			return null;
		}

		return Email::getOptoutURL() . '&c=' . $this->context_optout;
	}

	public function setRecipient(string $email, ?string $pgp_key = null)
	{
		$this->set('recipient', $email);
		$this->set('recipient_pgp_key', $pgp_key);
	}

	public function setReplyTo(string $email)
	{
		$this->set('repy_to', $email);
	}

	public function queue(): bool
	{
		return $this->save();
	}

	public function queueTo(array $recipients): void
	{
		foreach ($recipients as $address) {
			$msg = clone $this;
			$msg->setRecipient($address);
			$msg->queue();
		}
	}

	public function createSMTPMessage(): Mail_Message
	{

		$config = Config::getInstance();
		$message = new Mail_Message;

		$message->setHeader('From', $this->sender ?? self::getDefaultFromHeader());
		$message->setHeader('To', $this->recipient);
		$message->setHeader('Subject', $this->subject);

		if (!$message->getFrom()) {
			$message->setHeader('From', self::getFromHeader());
		}

		if (MAIL_SENDER) {
			$message->setHeader('Reply-To', $message->getFromAddress());
			$message->setHeader('From', self::getFromHeader($message->getFromName(), MAIL_SENDER));
		}

		if ($this->reply_to) {
			$message->setHeader('Reply-To', $this->reply_to);
		}

		$message->setMessageId();

		$text = $this->body;
		$html = $this->body_html;

		// Append unsubscribe, except for password reminders
		if ($this->context != self::CONTEXT_SYSTEM) {
			$url = $this->getContextSpecificOptoutURL() ?? $this->getOptoutURL();

			// RFC 8058
			$message->setHeader('List-Unsubscribe', sprintf('<%s>', $url));
			$message->setHeader('List-Unsubscribe-Post', 'Unsubscribe=Yes');

			$text .= sprintf("\n\n-- \n%s\n\n%s\n%s", $config->org_name, $this->getOptoutText(), $url);

			if (null !== $html) {
				$html = $this->appendHTMLOptoutFooter();
			}
		}

		$message->setBody($text);

		if (null !== $html) {
			$message->setHTMLBody($html);
		}

		$message->setHeader('Return-Path', MAIL_RETURN_PATH ?? (MAIL_SENDER ?? $config->org_email));
		$message->setHeader('X-Auto-Response-Suppress', 'All'); // This is to avoid getting auto-replies from Exchange servers

		foreach ($attachments as $path) {
			$file = Files::get($path);

			if (!$file) {
				continue;
			}

			$message->addPart($file->mime, $file->fetch(), $file->name);
		}

		static $can_use_encryption = null;

		if (null === $can_use_encryption) {
			$can_use_encryption = Security::canUseEncryption();
		}

		if ($this->recipient_pgp_key && $can_use_encryption) {
			$message->encrypt($this->recipient_pgp_key);
		}

		return $message;
	}

	public function send(bool $in_queue = false): bool
	{
		if (DISABLE_EMAIL) {
			return false;
		}

		$message = $this->createSMTPMessage();
		$entity = $this;
		$context = $this->context;

		$signal = Plugins::fire('email.send.before', true, compact('message', 'context', 'entity'), ['sent' => null]);

		if ($signal && $signal->isStopped()) {
			return $signal->getOut('sent') ?? true;
		}

		if (SMTP_HOST) {
			static $smtp = null;
			static $count = 0;

			// Reset connection when we reach the max number of messages
			if (null !== $smtp && $count >= SMTP_MAX_MESSAGES_PER_SESSION) {
				$smtp->disconnect();
				$smtp = null;
			}

			// Re-use SMTP connection in queues
			if (null === $smtp) {
				$const = '\KD2\SMTP::' . strtoupper(SMTP_SECURITY);
				$secure = constant($const);

				$smtp = new SMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, $secure, SMTP_HELO_HOSTNAME);
			}

			try {
				$return = $smtp->send($message);
				// TODO: store return message from SMTP server
				$count++;
			}
			catch (SMTP_Exception $e) {
				// Handle invalid recipients addresses
				if ($r = $e->getRecipient()) {
					if ($e->getCode() >= 500) {
						// Don't retry delivering this email
						self::handleManualBounce($r, 'hard', $e->getMessage());
						$this->set('status', self::STATUS_FAIL);
						return true;
					}
					elseif ($e->getCode() === SMTP::GREYLISTING_CODE) {
						// Resend later (FIXME: only retry for X times)
						return false;
					}
					elseif ($e->getCode() >= 400) {
						self::handleManualBounce($r, 'soft', $e->getMessage());
						$this->set('status', self::STATUS_FAIL);
						return true;
					}
				}

				throw $e;
			}

			if (!$in_queue) {
				$smtp->disconnect();
				$smtp = null;
			}
		}
		else {
			// Send using PHP mail() function
			$message->send();
		}

		Plugins::fire('email.send.after', false, compact('context', 'message', 'entity'));
		$this->set('status', self::STATUS_SENT);
		return true;
	}
}