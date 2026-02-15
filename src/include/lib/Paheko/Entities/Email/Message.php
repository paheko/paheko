<?php

namespace Paheko\Entities\Email;

use Paheko\Config;
use Paheko\Entity;
use Paheko\Utils;
use Paheko\Entities\Files\File;
use Paheko\Entities\Users\User;
use Paheko\UserTemplate\UserTemplate;
use Paheko\Web\Render\Render;

use const Paheko\{
	DISABLE_EMAIL,
	MAIL_RETURN_PATH,
	MAIL_SENDER,
	SMTP_HOST,
	SMTP_PORT,
	SMTP_USER,
	SMTP_PASSWORD,
	SMTP_SECURITY,
	SMTP_HELO_HOSTNAME,
	SMTP_MAX_MESSAGES_PER_SESSION,
	LOCAL_SECRET_KEY,
	WWW_URL
};

use KD2\DB\EntityManager as EM;
use KD2\HTML\CSSParser;
use KD2\Mail_Message;
use KD2\Smartyer;
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
	protected int $status = self::STATUS_WAITING;

	protected DateTime $added;
	protected ?DateTime $modified = null;

	protected ?string $sender = null;
	protected ?string $reply_to = null;

	protected string $recipient;
	protected ?string $recipient_name = null;
	protected ?string $recipient_pgp_key = null;
	protected int $id_recipient;
	protected ?int $id_user = null;

	protected string $subject;
	protected string $body;
	protected ?string $body_html = null;
	protected ?string $headers = null;

	protected ?UserTemplate $_template = null;
	protected bool $_rendered = false;

	// We don't store the preheader as it will be put in the HTML
	protected ?string $_preheader = null;

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
		$this->assert(array_key_exists($this->context, self::CONTEXT_LIST));
		$this->assert(array_key_exists($this->status, self::STATUS_LIST));
		$this->assert(strlen($this->subject), 'Sujet vide');
		$this->assert(strlen($this->body), 'Corps vide');
		$this->assert(strlen($this->recipient), 'Destinataire absent');

		$this->assert($this->context !== self::CONTEXT_SYSTEM || !isset($this->body_html));
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
	public function wrapHTMLBody(): string
	{
		static $template = null;
		static $css_parser = null;

		// System emails don't have an HTML part
		if ($this->context === self::CONTEXT_SYSTEM) {
			throw new \LogicException('System emails don\'t have a HTML body');
		}

		$template ??= new UserTemplate('web/email.html');

		$body = $this->getHTMLBody();

		// For bulk sending, limit the number of external domains
		// by using redirect URLs
		if ($this->context === self::CONTEXT_BULK) {
			$body = self::replaceExternalLinksInHTML($body);
		}

		$template->assign('html', $body);
		$template->assign('preheader', $this->getHTMLPreheader());

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
					return $html;
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

		return $html;
	}

	public function setPreheader(string $text): void
	{
		$this->_preheader = $text;
	}

	public function getPreheader(bool $from_html = false): string
	{
		$text = $this->_preheader;

		// Create a preheader from the body
		if ($from_html || null === $text) {
			$text = $this->getHTMLBody();
			$text = strip_tags($text);
			$text = trim(htmlspecialchars_decode($text));
			$text = preg_replace('/\s+/', ' ', $text);
		}

		$text = Smartyer::truncate($text, 130, '…', true);
		return $text;
	}

	public function getHTMLPreheader(): string
	{
		$text = $this->getPreheader();

		// This is important for gmail, see https://github.com/hteumeuleu/email-bugs/issues/41#issuecomment-1321464561
		$text = htmlentities($text);

		// Add some spacing after pre-header text just in case it is too short,
		// so that the rest of the email content doesn't show
		$length = mb_strlen($text);
		$text .= str_repeat('&#847;&zwnj;&nbsp;', 130 - $length);

		$text = sprintf('<span style="display: none !important; visibility: hidden; mso-hide: all; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">%s</span>', $text);

		return $text;
	}


	public function getBody(): string
	{
		$this->render();
		return $this->body;
	}

	public function getHTMLBody(): string
	{
		$this->render();
		return $this->body_html;
	}

	public function setBody(string $str, bool $allow_template = false)
	{
		if ($allow_template
			&& false !== strpos($str, '{{')
			&& strpos($str, '}}') > strpos($str, '{{')) {
			$this->setBodyTemplateFromString($str);
		}

		$this->set('body', $str);
	}

	protected function setBodyTemplateFromString(string $str)
	{
		// For email templates, allow {{name}} or {{identity}} without the dollar sign, except {{if}} {{literal}} etc.
		$str = preg_replace('/\{\{\s*((?:(?!if|else|elseif|literal)[a-z_])+?(?:\s*\|\s*[a-z0-9_]+))\s*\}\}/i', '{{\$\$1}}', $str);
		$str = '{{**keep_whitespaces**}}' . $str;
		$tpl = UserTemplate::createFromUserString($str);

		// Disable escaping, as the string will be escaped by the Markdown renderer
		$tpl->setEscapeDefault(null);

		$this->_template = $tpl;
		$this->_rendered = false;
	}

	/**
	 * Render HTML and plaintext body
	 * @param  array|null $data If NULL then Brindille code inside the body will not be interpreted
	 */
	public function render(?array $data = []): void
	{
		// Don't render the markdown for each message
		// when the object has been cloned (eg. for bull messages)
		if ($this->_rendered) {
			return;
		}

		if (null !== $data
			&& null !== $this->_template) {
			// Replace placeholders in template: {{$name}}, etc.
			$this->_template->assignArray($data, null, false);

			try {
				$body = $this->_template->fetch();
			}
			catch (Brindille_Exception $e) {
				throw new UserException('Erreur de syntaxe dans le corps du message :' . PHP_EOL . $e->getMessage(), 0, $e);
			}
		}
		else {
			$body = $this->body;
		}

		// System messages don't have any rendering
		$markdown = in_array($this->context, [self::CONTEXT_BULK, self::CONTEXT_PRIVATE]);

		if ($markdown) {
			// Force grid to output as tables in emails
			$body = preg_replace('/<<grid\s+([^!#].*?)>>/', '<<grid legacy $1>>', $body);
			$body = preg_replace('/<<grid\s+([!#]+)\s*>>/', '<<grid legacy short="$1">>', $body);

			// Render to HTML
			$html = Render::render(Render::FORMAT_MARKDOWN, null, $body);

			// Remove some of markdown code from plaintext email
			$body = Render::render(Render::FORMAT_PLAINTEXT, null, $body);
		}
		else {
			$html = Utils::linkifyURLs(nl2br(htmlspecialchars($body)));
		}

		$this->set('body_html', $html);
		$this->set('body', $body);

		$this->_rendered = true;
	}

	public function getTextPreview(bool $append_footer = false, array $data = []): string
	{
		$this->render($data);

		$text = $this->body;

		if ($append_footer) {
			$text = $this->appendTextFooter($text, '[lien de désinscription]');
		}

		return $text;
	}

	public function getHTMLPreview(bool $append_footer = false, array $data = []): string
	{
		$this->render($data);

		$html = $this->body_html;
		$html = $this->wrapHTMLBody($html);

		if ($append_footer) {
			$html = $this->appendHTMLFooter($html, 'javascript:alert(\'Le lien de désinscription est désactivé dans la prévisualisation.\');');
		}

		$html = str_replace('</head>',
			'<style type="text/css">
			body {
				background: #fff;
				color: #000;
				margin: 10px;
				font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
			}
			</style>',
			$html);

		return $html;
	}

	protected function getOptoutText(): string
	{
		return "Vous recevez ce message car vous êtes dans nos contacts.\n"
			. "Pour ne plus recevoir ces messages cliquez ici :\n";
	}

	/**
	 * @see https://www.nngroup.com/articles/unsubscribe-mistakes/
	 */
	protected function appendHTMLFooter(string $html, string $url): string
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

	protected function appendTextFooter(string $text, string $url)
	{
		$config = Config::getInstance();

		return $text . sprintf("\n\n-- \n%s\n\n%s\n%s",
			$config->org_name,
			$this->getOptoutText(),
			$url
		);
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
		// Wrap HTML body inside email HTML template
		$this->set('body_html', $this->wrapHTMLBody());

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

		// Generate a feedback-ID, see https://support.google.com/a/answer/6254652?hl=en
		$feedback_id = sprintf('%s:id-%d:c-%d:%s',
			'pko', // unused yet
			$this->id_mailing ?? 0,
			$this->context,
			self::getSenderId()
		);

		// Add header for test recipients
		if (MAIL_TEST_RECIPIENTS
			&& in_array($this->recipient, MAIL_TEST_RECIPIENTS, true)) {
			$message->setHeader('X-Is-Recipient', 'Yes');
		}

		$message->setHeader('Feedback-ID', $feedback_id);
		$message->setHeader('From', $this->sender ?? self::getDefaultFromHeader());
		$message->setHeader('Subject', $this->subject);

		if ($this->recipient_name) {
			$message->setHeader('To', self::getAddressHeader($this->recipient_name, $this->recipient));
		}
		else {
			$message->setHeader('To', $this->recipient);
		}

		if (!$message->getFrom()) {
			$message->setHeader('From', self::getFromHeader());
		}

		if (MAIL_SENDER
			&& false === strpos(MAIL_SENDER, $message->getHeader('From'))) {
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
			$url = $this->getOptoutURL($this->context);

			// RFC 8058
			$message->setHeader('List-Unsubscribe', sprintf('<%s>', $url));
			$message->setHeader('List-Unsubscribe-Post', 'Unsubscribe=Yes');

			$text = $this->appendTextFooter($text, $url);

			if (null !== $html) {
				$html = $this->appendHTMLFooter($html, $url);
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

	/**
	 * 15 characters (max) long identifier for the sender
	 * "It should be consistent across the mail stream."
	 * @see https://support.google.com/a/answer/6254652?hl=en
	 */
	static public function getSenderId(): string
	{
		$id = parse_url(WWW_URL, PHP_URL_HOST);
		$id = trim(substr(str_replace('.', '-', ), 0, 15), '-');
		return $id;
	}

	static public function getAddressHeader(string $name, string $address): string
	{
		$name = str_replace('"', '\\"', $name);
		$name = str_replace(',', '', $name); // Remove commas

		return sprintf('"%s" <%s>', $name, $address);
	}

	static public function getFromHeader(?string $name = null, ?string $email = null): string
	{
		$config = Config::getInstance();
		return self::getAddressHeader($name ?? $config->org_name, $email ?? $config->org_email);
	}

	/**
	 * Redirect to external resource
	 * @return exit|null|string Will return a string if the signed link has expired but is still valid
	 */
	static public function redirectURL(string $str): ?string
	{
		$params = explode(':', $str, 3);

		if (count($params) !== 3) {
			return null;
		}

		if (!ctype_digit($params[1])) {
			return null;
		}

		if (strlen($params[0]) !== 40) {
			return null;
		}

		$hash = hash_hmac('sha1', $params[1] . $params[2], LOCAL_SECRET_KEY);

		$url = 'https://' . $params[2];

		if ($hash !== $params[0]) {
			return null;
		}

		// If the link has expired, the user should be prompted to redirect
		if ($params[1] < time()) {
			return $url;
		}

		Utils::redirect($url);
		return null;
	}

	/**
	 * Sign (HMAC) external links in mailing body,
	 * to make sure that we are using the same URL everywhere
	 * and limit the number of external domains used.
	 */
	static public function encodeURL(string $url): string
	{
		$parts = parse_url($url);

		if (empty($parts['scheme'])
			|| ($parts['scheme'] !== 'http' && $parts['scheme'] !== 'https')) {
			return $url;
		}

		// Don't do redirects for URLs from the same domain name
		if (Utils::isLocalURL($url)) {
			return $url;
		}

		$url = preg_replace('!^https?://!', '', $url);
		$expiry = time() + 3600*24*365;
		$hash = hash_hmac('sha1', $expiry . $url, LOCAL_SECRET_KEY);

		$param = sprintf('%s:%s:%s', $hash, $expiry, $url);
		return WWW_URL . '?rd=' . rawurlencode($param);
	}

	static public function replaceExternalLinksInHTML(string $html): string
	{
		// Replace external links with redirect URL
		// But don't trigger phishing detection for external links
		// eg. <a href="https://example.org/">https://example.org/</a>
		// shouldn't be changed to
		// <a href="https://paheko.example.org/?rd=example.org">https://example.org/</a>
		// so we are replacing the text of the link as well
		$html = preg_replace_callback('!(<a[^>]*href=")([^"]*)("[^>]*>)(.*)</a>!U', function ($match) {
			$text = $match[4];

			$url = self::encodeURL($match[2]);

			// Only replace content if URL is external
			if ($match[2] === $match[4]
				&& $match[2] !== $url) {
				$text = '[cliquer ici]';
			}

			return $match[1] . $url . $match[3] . $text . '</a>';
		}, $html);

		return $html;
	}
}
