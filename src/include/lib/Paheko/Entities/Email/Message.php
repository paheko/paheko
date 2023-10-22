<?php

class Message
{
	const TABLE = 'emails_queue';

	protected int $context;

	protected ?string $sender;
	protected string $recipient;
	protected string $recipient_hash;
	protected string $recipient_pgp_key;

	protected string $subject;
	protected string $body;
	protected string $body_html;
	protected array $attachments;

	protected ?string $context_optout;

	public function setBodyFromUserTemplate(UserTemplate $template, array $data = []): void
	{
		// Replace placeholders: {{$name}}, etc.
		$template->assignArray((array) $data, null, false);

		// Disable HTML escaping for plaintext emails
		$template->setEscapeDefault(null);
		$this->body = $template->fetch();

		if ($markdown) {
			// Use Markdown rendering for HTML emails
			$this->body_html = Render::render(Render::FORMAT_MARKDOWN, null, $this->body);
		}
	}

	public function createHTMLFromMarkdownBody(?string $body = null): void
	{
		if (null !== $body) {
			$this->body = $body;
		}

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
			$button = sprintf('<a href="%s" style="color: #009; text-decoration: underline; padding: 3px 6px; border-radius: 3px; background: #ddd; border: 1px outset #999;">Me désinscrire de ces envois uniquement</a></p>', $this->getContextSpecificOptoutURL());
			$text = str_replace('[context_optout_url]', $button, $text);
		}

		$button = sprintf('<a href="%s" style="color: #009; text-decoration: underline; padding: 3px 6px; border-radius: 3px; background: #ddd; border: 1px outset #999;">Me désinscrire de <b>tous les envois</b></a></p>', $this->getOptoutURL());
		$text = str_replace('[optout_url]', $button, $text);

		$footer = '<hr style="border-top: 2px solid #999; background: none;" /><p style="color: #666; background: #fff; padding: 10px; text-align: center; font-size: 9pt">';
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


	public function save(bool $selfcheck = true): bool
	{

	}

	public function send()
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

		Emails::sendMessage($context, $message);
	}
}