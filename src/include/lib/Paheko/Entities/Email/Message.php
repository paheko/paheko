<?php

namespace Paheko\Entities\Email;

use Paheko\Config;
use Paheko\Entity;
use Paheko\Entities\Files\File;
use Paheko\Entities\Users\User;
use Paheko\UserTemplate\UserTemplate;

use const Paheko\{DISABLE_EMAIL, MAIL_RETURN_PATH, MAIL_SENDER, MAIL_};
use const Paheko\{
	SMTP_HOST,
	SMTP_PORT,
	SMTP_USER,
	SMTP_PASSWORD,
	SMTP_SECURITY,
	SMTP_HELO_HOSTNAME,
	SMTP_MAX_MESSAGES_PER_SESSION
};

use KD2\DB\EntityManager as EM;
use KD2\HTML\CSSParser;
use KD2\Mail_Message;
use KD2\SMTP;
use KD2\SMTP_Exception;
use KD2\Security;

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

	protected ?UserTemplate $_template = null;
	protected bool $_rendered = false;

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

	public function set(string $key, $value)
	{
		parent::set($key, $value);

		// If we change the body or HTML body, then we need to re-render
		if (($key === 'body' || $key === 'body_html')
			&& $this->isModified($key)) {
			$this->_rendered = false;
		}
	}

	/**
	 * This will wrap the message HTML contents inside
	 * the main email template, parse the CSS, and apply all
	 * the CSS rules in the 'style' attribute of each tag.
	 * Then the style tag is deleted.
	 * If the CSS parsing fails, the style tag is left as-is.
	 */
	public function wrapHTMLBody(): void
	{
		static $template = null;
		static $css_parser = null;

		// System emails don't have an HTML part
		if ($this->context === self::CONTEXT_SYSTEM) {
			return;
		}

		$template ??= new UserTemplate('web/email.html');

		$template->assign('html', $this->getHTMLBody());
		$html = $template->fetch();

		// If CSS parser is FALSE, this means the parsing of the CSS file failed
		// then don't try to apply CSS, unless we want to make sure the style
		if ($css_parser !== false) {
			libxml_use_internal_errors(true);
			$doc = new \DOMDocument;
			$doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

			// Parse CSS style only once
			if (null === $css_parser) {
				try {
					$css_parser = new CSSParser;
					$style_tag = $css_parser->xpath($doc, '//style', 0);
					$css_parser->import($style_tag->textContent);
				}
				catch (\InvalidArgumentException $e) {
					$css_parser = false;
					unset($doc);
					libxml_use_internal_errors(false);
					$this->set('body_html', $html);
					return;
				}
			}

			// Then apply CSS styles to each tag, by adding a 'style' attribute
			$css_parser->style($doc->documentElement);

			// Delete the style tag
			$style_tag = $css_parser->xpath($doc, '//style', 0);
			$style_tag->parentNode->removeChild($style_tag);

			// Re-export document
			$html = $doc->saveHTML($doc->documentElement);

			unset($doc);

			libxml_use_internal_errors(false);
		}

		$this->set('body_html', $html);
	}

	public function setBodyTemplateFromString(string $str)
	{
		$str = '{{**keep_whitespaces**}}' . $str;
		$tpl = UserTemplate::createFromUserString($str);

		// Disable escaping, as the string will be escaped by the Markdown renderer
		$tpl->setEscapeDefault(null);

		$this->_template = $tpl;
		$this->_rendered = false;
	}

	public function createBody(array $user_data = []): void
	{
		// Don't render the markdown for each message
		// when the object has been cloned (eg. for bull messages)
		if ($this->_rendered) {
			return;
		}

		if (null !== $this->_template()) {
			// Replace placeholders in template: {{$name}}, etc.
			$this->_template->assignArray($user_data, null, false);
			$body = $this->_template->fetch();
		}
		else {
			$body = $this->body;
		}

		// Render to HTML
		$html = Render::render(Render::FORMAT_MARKDOWN, null, $body);

		// For bulk sending, limit the number of external domains
		// by using redirect URLs
		if ($context === self::CONTEXT_BULK) {
			$content_html = self::replaceExternalLinksInHTML($content_html);
		}

		$this->set('body_html', $html);

		// Remove some of markdown code from plaintext email
		$text = Render::render(Render::FORMAT_PLAINTEXT, null, $body);
		$this->set('body', $body);

		$this->_rendered = true;
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
		return $this->recipient()->getOptoutURL($this->context);
	}

	public function setRecipient(string $email, ?int $id_user, ?string $pgp_key = null)
	{
		$this->set('recipient', $email);
		$this->set('id_', $email);
		$this->set('recipient_pgp_key', $pgp_key);
	}

	public function setReplyTo(string $email)
	{
		$this->set('reply_to', $email);
	}

	public function listAttachments(): array
	{
		$em = EntityManager::getInstance(File::class);
		return $em->all('SELECT f.* FROM @TABLE f INNER JOIN emails_queue_attachments a ON a.id_file = f.id WHERE a.id_message = ?;', $this->id());
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

		if (null !== $this->headers) {
			$message->setHeaders($this->headers);
		}

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

		if ($headers['X-Is-Recipient'] ?? null === 'Yes') {
			$message->setMessageId('pko.' . $message->getMessageId());
		}

		$text = $this->body;
		$html = $this->body_html;

		// Append unsubscribe, except for password reminders
		if ($this->context != self::CONTEXT_SYSTEM) {
			$url = $this->getOptoutURL();

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

		foreach ($this->listAttachments() as $file) {
			$message->addPart($file->mime, $file->fetch(), $file->name);
		}

		static $can_use_encryption = null;
		$can_use_encryption ??= Security::canUseEncryption();

		if ($this->recipient_pgp_key
			&& $can_use_encryption) {
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

	static public function getFromHeader(?string $name = null, ?string $email = null): string
	{
		$config = Config::getInstance();

		if (null === $name) {
			$name = $config->org_name;
		}
		if (null === $email) {
			$email = $config->org_email;
		}

		$name = str_replace('"', '\\"', $name);
		$name = str_replace(',', '', $name); // Remove commas

		return sprintf('"%s" <%s>', $name, $email);
	}
}
