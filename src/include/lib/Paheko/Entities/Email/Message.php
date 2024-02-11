<?php

namespace Paheko\Entities\Email;

use Paheko\Config;
use Paheko\Entity;

use const Paheko\{DISABLE_EMAIL, MAIL_RETURN_PATH, MAIL_SENDER};
use const Paheko\{SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_SECURITY, SMTP_HELO_HOSTNAME};

use KD2\SMTP;
use KD2\Security;
use KD2\Mail_Message;

class Message extends Entity
{
	const TABLE = 'emails_queue';

	protected int $context;
	protected int $status = self::WAITING;

	protected ?string $sender;
	protected string $recipient;
	protected string $recipient_hash;
	protected ?string $recipient_pgp_key;

	protected string $subject;
	protected string $body;
	protected string $html_body;
	protected array $attachments;

	protected ?string $context_optout;

	const STATUS_WAITING = 0;
	const STATUS_SENDING = 1;
	const STATUS_SENT = 2;

	const STATUS_LIST = [
		self::STATUS_WAITING => 'En attente',
		self::STATUS_SENDING => 'Envoi en cours',
		self::STATUS_SENT    => 'Envoyé',
	];

	const STATUS_COLORS = [
		self::STATUS_WAITING => 'cadetblue',
		self::STATUS_SENDING => 'chocolate',
		self::STATUS_SENT    => 'darkgreen',
	];

	const CONTEXT_SYSTEM = 0;
	const CONTEXT_BULK = 1;
	const CONTEXT_PRIVATE = 2;
	const CONTEXT_NOTIFICATION = 3;

	const CONTEXT_LIST = [
		self::CONTEXT_SYSTEM => 'Système',
		self::CONTEXT_BULK => 'Collectif',
		self::CONTEXT_PRIVATE => 'Privé',
		self::CONTEXT_NOTIFICATION => 'Notification',
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

	public function getOptoutText(): string
	{
		$out = "Vous recevez ce message car vous êtes dans nos contacts.\n";

		if (isset($this->context_optout)) {
			$out .= "Pour vous désinscrire uniquement de ces envois, cliquez ici :\n";
			$out .= "[context_optout_url]\n\n";
		}

		$out .= "Pour ne plus jamais recevoir aucun message de notre part cliquez ici :\n";
		$out .= "[optout_url]\n\n";
		return $out;

	}

	public function getOptoutFooter(): string
	{
		return strtr($this->getOptoutText(), [
			'[context_optout_url]' => $this->getContextSpecificOptoutURL(),
			'[optout_url]' => $this->getOptoutURL(),
		]);
	}

	public function appendHTMLOptoutFooter(): string
	{
		$text = nl2br(htmlspecialchars($this->getOptoutText()));

		if (isset($this->context_optout)) {
			$button = sprintf('<a href="%s" style="color: #009; text-decoration: underline; padding: 5px 10px; border-radius: 5px; background: #eee; border: 1px outset #ccc;">Me désinscrire de ces envois uniquement</a></p>', $this->getContextSpecificOptoutURL());
			$text = str_replace('[context_optout_url]', $button, $text);
		}

		$button = sprintf('<a href="%s" style="color: #009; text-decoration: underline; padding: 5px 10px; border-radius: 3px; background: #eee; border: 1px outset #ccc;">Me désinscrire de <b>tous les envois</b></a></p>', $this->getOptoutURL());
		$text = str_replace('[optout_url]', $button, $text);

		$footer = '<p style="color: #666; background: #fff; padding: 10px; text-align: center; font-size: 9pt">';
		$footer .= $text;

		$html = $this->body_html;

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

	public function queue(): bool
	{
		return $this->save();
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

		$message->setMessageId();

		$text = $this->body;
		$html = $this->body_html;

		// Append unsubscribe, except for password reminders
		if ($this->context != self::CONTEXT_SYSTEM) {
			$url = $this->getContextSpecificOptoutURL() ?? $this->getOptoutURL();

			// RFC 8058
			$message->setHeader('List-Unsubscribe', sprintf('<%s>', $url));
			$message->setHeader('List-Unsubscribe-Post', 'Unsubscribe=Yes');

			$text .= sprintf("\n\n-- \n%s\n\n%s", $config->org_name, $this->getOptoutText());

			if (null !== $html) {
				$html = $this->appendHTMLOptoutFooter();
			}
		}

		$message->setBody($text);

		if (null !== $html) {
			$message->addPart('text/html', $html);
		}

		$message->setHeader('Return-Path', MAIL_RETURN_PATH ?? $config->org_email);
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

	public function send(): bool
	{
		if (DISABLE_EMAIL) {
			return false;
		}

		$message = $this->createSMTPMessage();
		$entity = $this;
		$context = $this->context;
		$fail = false;

		try {
			$signal = Plugins::fire('email.send.before', true, compact('message', 'context', 'entity'), ['sent' => null]);

			if ($signal && $signal->isStopped()) {
				return $signal->getOut('sent') ?? true;
			}

			if (SMTP_HOST) {
				$const = '\KD2\SMTP::' . strtoupper(SMTP_SECURITY);
				$secure = constant($const);

				$smtp = new SMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, $secure, SMTP_HELO_HOSTNAME);

				$smtp->send($message);
			}
			else {
				// Send using PHP mail() function
				$message->send();
			}

			Plugins::fire('email.send.after', false, compact('context', 'message', 'entity'));
			return true;
		}
		catch (\Throwable $e) {
			$fail = true;
		}
		finally {
			if (!$fail) {
				$this->set('status', self::STATUS_SENT);
			}
		}
	}
}