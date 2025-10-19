<?php

namespace Paheko\Email;

use Paheko\Config;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\Entities\Email\Email;
use Paheko\Entities\Files\File;
use Paheko\Entities\Users\User;
use Paheko\Users\DynamicFields;
use Paheko\UserTemplate\UserTemplate;
use Paheko\Web\Render\Render;

use Paheko\Files\Files;

use const Paheko\{USE_CRON, MAIL_SENDER, MAIL_RETURN_PATH, DISABLE_EMAIL, WWW_URL, ADMIN_URL, SECRET_KEY};
use const Paheko\{SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_SECURITY, SMTP_HELO_HOSTNAME};

use KD2\SMTP;
use KD2\SMTP_Exception;
use KD2\Security;
use KD2\Mail_Message;
use KD2\DB\EntityManager as EM;

class Emails
{
	const RENDER_FORMATS = [
		null => 'Texte brut',
		Render::FORMAT_MARKDOWN => 'MarkDown',
	];

	/**
	 * Email sending contexts
	 */
	const CONTEXT_BULK = 1;
	const CONTEXT_PRIVATE = 2;
	const CONTEXT_SYSTEM = 0;
	const CONTEXT_NOTIFICATION = 3;
	const CONTEXT_REMINDER = 4;

	/**
	 * When we reach that number of fails, the address is treated as permanently invalid, unless reset by a verification.
	 */
	const FAIL_LIMIT = 5;

	/**
	 * Add a message to the sending queue using templates
	 * @param  int          $context
	 * @param  iterable        $recipients List of recipients, this accepts a wide range of types:
	 * - a single e-mail address
	 * - array of e-mail addresses as values ['a@b.c', 'd@e.f']
	 * - array of user entities
	 * - array where each key is the email address, and the value is an array or a \stdClass containing
	 *   pgp_key, data and user items
	 * @param  string       $sender
	 * @param  string       $subject
	 * @param  UserTemplate|string $content
	 * @return void
	 */
	static public function queue(int $context, iterable $recipients, ?string $sender, string $subject, $content, array $attachments = []): void
	{
		if (DISABLE_EMAIL) {
			return;
		}

		foreach ($attachments as $i => $file) {
			if (!is_object($file) || !($file instanceof File) || $file->context() != $file::CONTEXT_ATTACHMENTS) {
				throw new \InvalidArgumentException(sprintf('Attachment #%d is not a valid file', $i));
			}
		}

		$list = [];

		// Build email list
		foreach ($recipients as $key => $r) {
			$data = [];
			$emails = [];
			$user = null;
			$pgp_key = null;

			if (is_array($r)) {
				$user = $r['user'] ?? null;
				$data = $r['data'] ?? null;
				$pgp_key = $r['pgp_key'] ?? null;
			}
			elseif (is_object($r) && $r instanceof User) {
				$user = $r;
				$data = $r->asArray();
				$pgp_key = $user->pgp_key ?? null;
			}
			elseif (is_object($r)) {
				$user = $r->user ?? null;
				$data = $r->data ?? null;
				$pgp_key = $user->pgp_key ?? ($r->pgp_key ?? null);
			}

			// Get e-mail address from key
			if (is_string($key) && false !== strpos($key, '@')) {
				$emails[] = $key;
			}
			// Get e-mail address from value
			elseif (is_string($r) && false !== strpos($r, '@')) {
				$emails[] = $r;
			}
			// Get email list from user object
			elseif ($user) {
				$emails = $user->getEmails();
			}
			else {
				// E-mail not found
				continue;
			}

			// Filter out invalid addresses
			foreach ($emails as $key => $value) {
				if (!preg_match('/.+@.+\..+$/', $value)) {
					unset($emails[$key]);
				}
			}

			if (!count($emails)) {
				continue;
			}

			$data = compact('user', 'data', 'pgp_key');

			foreach ($emails as $value) {
				$list[$value] = $data;
			}
		}

		if (!count($list)) {
			return;
		}

		$recipients = $list;
		unset($list);

		$is_system = $context === self::CONTEXT_SYSTEM;
		$template = (!$is_system && $content instanceof UserTemplate) ? $content : null;

		if ($template) {
			$template->toggleSafeMode(true);
		}

		$signal = Plugins::fire('email.queue.before', true,
			compact('context', 'recipients', 'sender', 'subject', 'content', 'attachments'));

		// queue handling was done by a plugin, stop here
		if ($signal && $signal->isStopped()) {
			return;
		}

		$db = DB::getInstance();
		$db->begin();
		$html = null;
		$main_tpl = null;

		// Apart from SYSTEM emails, all others should be wrapped in the email.html template
		if (!$is_system) {
			$main_tpl = new UserTemplate('web/email.html');
		}

		if (!$is_system && !$template) {
			// If E-Mail does not have placeholders, we can render the MarkDown just once for HTML
			$html = Render::render(Render::FORMAT_MARKDOWN, null, $content);
		}

		foreach ($recipients as $recipient => $r) {
			$data = $r['data'];
			$recipient_pgp_key = $r['pgp_key'];

			// We won't try to reject invalid/optout recipients here,
			// it's done in the queue clearing (more efficient)
			$recipient_hash = Email::getHash($recipient);

			// Replace placeholders: {{$name}}, etc.
			if ($template) {
				$template->assignArray((array) $data, null, false);

				// Disable HTML escaping for plaintext emails
				$template->setEscapeDefault(null);
				$content = $template->fetch();

				// Add Markdown rendering
				$content_html = Render::render(Render::FORMAT_MARKDOWN, null, $content);
			}
			else {
				$content_html = $html;
			}

			if ($context === self::CONTEXT_BULK) {
				$content_html = self::replaceExternalLinksInHTML($content_html);
			}

			if (!$is_system) {
				// Wrap HTML content in the email skeleton
				$main_tpl->assignArray([
					'html'      => $content_html,
					'address'   => $recipient,
					'data'      => $data,
					'context'   => $context,
					'from'      => $sender,
				]);

				$content_html = $main_tpl->fetch();
			}

			$signal = Plugins::fire('email.queue.insert', true,
				compact('context', 'recipient', 'sender', 'subject', 'content', 'recipient_hash', 'recipient_pgp_key', 'content_html', 'attachments'));

			if ($signal && $signal->isStopped()) {
				// queue insert was done by a plugin, stop here
				continue;
			}

			unset($signal);

			$db->insert('emails_queue', compact('sender', 'subject', 'context', 'recipient', 'recipient_pgp_key', 'recipient_hash', 'content', 'content_html'));

			// Clean up memory
			unset($content_html);

			$id = $db->lastInsertId();

			foreach ($attachments as $file) {
				$db->insert('emails_queue_attachments', ['id_queue' => $id, 'path' => $file->path]);
			}
		}

		$db->commit();

		$signal = Plugins::fire('email.queue.after', true,
			compact('context', 'recipients', 'sender', 'subject', 'content', 'attachments'));

		if ($signal && $signal->isStopped()) {
			return;
		}

		// If no crontab is used, then the queue should be run now
		if (!USE_CRON) {
			self::runQueue();
		}
		// Always send system emails right away
		elseif ($is_system) {
			self::runQueue(self::CONTEXT_SYSTEM);
		}
	}

	/**
	 * Return an Email entity from the optout code
	 */
	static public function getEmailFromQueryStringValue(string $code): ?Email
	{
		$hash = base64_decode(str_pad(strtr($code, '-_', '+/'), strlen($code) % 4, '=', STR_PAD_RIGHT));

		if (!$hash) {
			return null;
		}

		$hash = bin2hex($hash);
		return EM::findOne(Email::class, 'SELECT * FROM @TABLE WHERE hash = ?;', $hash);
	}

	/**
	 * Sets the address as invalid (no email can be sent to this address ever)
	 */
	static public function markAddressAsInvalid(string $address): void
	{
		$e = self::getEmail($address);

		if (!$e) {
			return;
		}

		$e->set('invalid', true);
		$e->set('optout', false);
		$e->set('verified', false);
		$e->save();
	}

	/**
	 * Return an Email entity from an email address
	 */
	static public function getEmail(string $address): ?Email
	{
		return EM::findOne(Email::class, 'SELECT * FROM @TABLE WHERE hash = ?;', Email::getHash(strtolower($address)));
	}

	/**
	 * Return or create a new email entity
	 */
	static public function getOrCreateEmail(string $address): Email
	{
		$hash = Email::getHash($address);

		$e = EM::findOne(Email::class, 'SELECT * FROM @TABLE WHERE hash = ?;', $hash);
		$e ??= self::createEmail($address, $hash);

		return $e;
	}

	static public function createEmail(string $address, string $hash): Email
	{
		$e = new Email;
		$e->set('added', new \DateTime);
		$e->set('hash', $hash);
		$e->validate($address);
		$e->save();
		return $e;
	}

	/**
	 * Run the queue of emails that are waiting to be sent
	 */
	static public function runQueue(?int $context = null): ?int
	{
		$db = DB::getInstance();

		$queue = self::listQueueAndMarkAsSending($context);
		$ids = [];

		$save_sent = function () use (&$ids, $db) {
			if (!count($ids)) {
				return null;
			}

			$db->exec(sprintf('UPDATE emails_queue SET sending = 2 WHERE %s;', $db->where('id', $ids)));
			$ids = [];
		};

		$limit_time = strtotime('1 month ago');
		$count = 0;
		$all_attachments = [];

		// listQueue nettoie déjà la queue
		foreach ($queue as $row) {
			// See if we need to avoid this recipient
			if (!Email::acceptsThisMessage($row)) {
				self::deleteFromQueue($row->id);
				continue;
			}

			$fail = null;
			$fail_message = null;

			try {
				Email::validateAddress($row->recipient, true);
			}
			catch (UserException $e) {
				$fail = 'hard';
				$fail_message = $e->getMessage();
			}

			if (!$fail && $row->context !== self::CONTEXT_SYSTEM) {
				// Allow a signal to validate the email address (eg. global list validation)
				$signal = Plugins::fire('email.address.check', true, ['hash' => $row->email_hash, 'address' => $row->recipient]);

				if ($signal && $signal->isStopped()) {
					$fail = $signal->getOut('fail');
					$fail_message = $signal->getOut('message');
				}
			}

			if ($fail) {
				$address = self::getOrCreateEmail($row->recipient);
				$address->hasBounced($fail, $fail_message);
				$address->save();

				// Skip, email address cannot receive emails
				self::deleteFromQueue($row->id);
				continue;
			}

			$headers = [
				'From'    => $row->sender,
				'To'      => $row->recipient,
				'Subject' => $row->subject,
			];

			try {
				$attachments = $db->getAssoc('SELECT id, path FROM emails_queue_attachments WHERE id_queue = ?;', $row->id);
				$all_attachments = array_merge($all_attachments, $attachments);
				$sent = self::send($row->context, $row->recipient_hash, $headers, $row->content, $row->content_html, $row->recipient_pgp_key, $attachments, true);

				// Keep waiting until email is sent
				if (!$sent) {
					continue;
				}
			}
			catch (\Exception $e) {
				// If sending fails, at least save what has been sent so far
				// so they won't get re-sent again
				$save_sent();
				throw $e;
			}

			$ids[] = $row->id;
			$count++;

			// Mark messages as sent from time to time
			// to avoid starting from the beginning if the queue is killed
			// and also avoid passing too many IDs to SQLite at once
			if (count($ids) >= 50) {
				$save_sent();
			}
		}

		// Update emails list and send count
		// then delete messages from queue
		$db->begin();
		$db->exec(sprintf('
			UPDATE emails_queue SET sending = 2 WHERE %s;
			INSERT OR IGNORE INTO %s (hash) SELECT recipient_hash FROM emails_queue WHERE sending = 2;
			UPDATE %2$s SET sent_count = sent_count + 1, last_sent = datetime()
				WHERE hash IN (SELECT recipient_hash FROM emails_queue WHERE sending = 2);
			DELETE FROM emails_queue WHERE sending = 2;',
			$db->where('id', $ids),
			Email::TABLE));
		$db->commit();

		$unused_attachments = array_diff($all_attachments, $db->getAssoc('SELECT id, path FROM emails_queue_attachments;'));

		foreach ($unused_attachments as $path) {
			$file = Files::get($path);

			if ($file) {
				$file->delete();
			}
		}

		return $count;
	}

	/**
	 * Lists the queue, marks listed elements as "sending"
	 * @return array
	 */
	static protected function listQueueAndMarkAsSending(?int $context = null): array
	{
		$queue = self::listQueue($context);

		if (!count($queue)) {
			return $queue;
		}

		$ids = [];

		foreach ($queue as $row) {
			$ids[] = $row->id;
		}

		$db = DB::getInstance();
		$db->update('emails_queue', ['sending' => 1, 'sending_started' => new \DateTime], $db->where('id', $ids));

		return $queue;
	}

	/**
	 * Returns the lits of emails waiting to be sent, except invalid ones and emails that haved failed too much
	 *
	 * DO NOT USE for sending, use listQueueAndMarkAsSending instead, or there might be multiple processes sending
	 * the same email over and over.
	 *
	 * @param int|null $context Context to list, leave NULL to have all contexts
	 * @return array
	 */
	static protected function listQueue(?int $context = null): array
	{
		// Clean-up the queue from reject emails
		self::purgeQueueFromRejected();

		// Reset messages that failed during the queue run
		self::resetFailed();

		$condition = null === $context ? '' : sprintf(' AND context = %d', $context);

		return DB::getInstance()->get(sprintf('SELECT q.*, e.accepts_messages, e.accepts_mailings, e.accepts_reminders, e.verified, e.hash AS email_hash,
				e.invalid, e.fail_count, strftime(\'%%s\', e.last_sent) AS last_sent
			FROM emails_queue q
			LEFT JOIN emails e ON e.hash = q.recipient_hash
			WHERE q.sending = 0 %s;', $condition));
	}

	static public function countQueue(): int
	{
		return DB::getInstance()->count('emails_queue');
	}

	/**
	 * Supprime de la queue les messages liés à des adresses invalides
	 * ou qui ne souhaitent plus recevoir de message
	 * @return boolean
	 */
	static protected function purgeQueueFromRejected(): void
	{
		DB::getInstance()->delete('emails_queue',
			'recipient_hash IN (SELECT hash FROM emails WHERE (invalid = 1 OR fail_count >= ?)
			AND last_sent >= datetime(\'now\', \'-1 month\'));',
			self::FAIL_LIMIT);
	}

	/**
	 * If emails have been marked as sending but sending failed, mark them for resend after a while
	 */
	static public function resetFailed(bool $force = false): void
	{
		$condition = $force ? '' : 'AND sending_started < datetime(\'now\', \'-3 hours\')';

		$sql = sprintf('UPDATE emails_queue SET sending = 0, sending_started = NULL
			WHERE sending = 1 %s;', $condition);
		DB::getInstance()->exec($sql);
	}

	/**
	 * Supprime un message de la queue d'envoi
	 * @param  integer $id
	 * @return boolean
	 */
	static protected function deleteFromQueue($id)
	{
		return DB::getInstance()->delete('emails_queue', 'id = ?', (int)$id);
	}

	static public function getRejectionStatusClause(string $prefix): string
	{
		$prefix .= '.';

		return sprintf('CASE
			WHEN %1$saccepts_messages = 0 THEN \'Désinscription\'
			WHEN %1$sinvalid = 1 THEN \'Invalide\'
			WHEN %1$sfail_count >= %2$d THEN \'Trop d\'\'erreurs\'
			ELSE \'\'
		END', $prefix, self::FAIL_LIMIT);
	}

	static public function listInvalidUsers(): DynamicList
	{
		$db = DB::getInstance();
		$email_field = 'u.' . $db->quoteIdentifier(DynamicFields::getFirstEmailField());

		$columns = [
			'id' => [
				'select' => 'e.id',
			],
			'identity' => [
				'label' => 'Membre',
				'select' => DynamicFields::getNameFieldsSQL('u'),
			],
			'email' => [
				'label' => 'Adresse',
				'select' => $email_field,
			],
			'user_id' => [
				'select' => 'u.id',
			],
			'hash' => [
			],
			'status' => [
				'label' => 'Statut',
				'select' => self::getRejectionStatusClause('e'),
			],
			'sent_count' => [
				'label' => 'Messages envoyés',
			],
			'fail_log' => [
				'label' => 'Journal d\'erreurs',
			],
			'last_sent' => [
				'label' => 'Dernière tentative d\'envoi',
			],
			'accepts_messages' => [],
			'fail_count' => [],
		];

		$tables = sprintf('emails e INNER JOIN users u ON %s IS NOT NULL AND %1$s != \'\' AND e.hash = email_hash(%1$s)', $email_field);

		$conditions = sprintf('e.invalid = 1 OR e.fail_count >= %d', self::FAIL_LIMIT);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('last_sent', true);
		$list->setModifier(function (&$row) {
			$row->last_sent = $row->last_sent ? new \DateTime($row->last_sent) : null;
		});
		return $list;
	}

	static public function listOptoutUsers(string $type): DynamicList
	{
		if (!in_array($type, ['messages', 'reminders', 'mailings'], true)) {
			throw new \InvalidArgumentException('Invalid type: ' . $type);
		}

		$list = self::listInvalidUsers();
		$list->setConditions(sprintf('e.accepts_%s = 0', $type));
		$list->setColumnProperty('fail_log', 'label', 'Historique');
		$list->removeColumn('status');

		return $list;
	}

	static public function getOptoutText(): string
	{
		return "Vous recevez ce message car vous êtes dans nos contacts.\n"
			. "Pour ne plus recevoir ces messages cliquez ici :\n";
	}

	static public function appendHTMLOptoutFooter(string $html, string $url): string
	{
		$footer = '<p style="color: #666; background: #fff; padding: 10px; text-align: center; font-size: 9pt">' . nl2br(htmlspecialchars(self::getOptoutText()));
		$footer .= sprintf('<br /><a href="%s" style="color: #009; text-decoration: underline; padding: 5px 10px; border-radius: 5px; background: #eee; border: 1px outset #ccc;">Me désinscrire</a></p>', $url);

		if (stripos($html, '</body>') !== false) {
			$html = str_ireplace('</body>', $footer . '</body>', $html);
		}
		else {
			$html .= $footer;
		}

		return $html;
	}

	static protected function send(int $context, string $recipient_hash, array $headers, string $content, ?string $content_html, ?string $pgp_key = null, array $attachments = [], bool $in_queue = false): bool
	{
		$config = Config::getInstance();
		$message = new Mail_Message;
		$message->setHeaders($headers);

		if (!$message->getFrom()) {
			$message->setHeader('From', self::getFromHeader());
		}

		if (MAIL_SENDER) {
			$message->setHeader('Reply-To', $message->getFromAddress());
			$message->setHeader('From', self::getFromHeader($message->getFromName(), MAIL_SENDER));
		}

		$message->setMessageId();

		// Append unsubscribe, except for password reminders
		if ($context != self::CONTEXT_SYSTEM) {
			$url = Email::getOptoutURL($recipient_hash, $context);

			// RFC 8058
			$message->setHeader('List-Unsubscribe', sprintf('<%s>', $url));
			$message->setHeader('List-Unsubscribe-Post', 'Unsubscribe=Yes');

			$content .= sprintf("\n\n-- \n%s\n\n%s\n%s", $config->org_name, self::getOptoutText(), $url);

			if (null !== $content_html) {
				$content_html = self::appendHTMLOptoutFooter($content_html, $url);
			}
		}

		$message->setBody($content);

		if (null !== $content_html) {
			$message->addPart('text/html', $content_html);
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

		if ($pgp_key && $can_use_encryption) {
			$message->encrypt($pgp_key);
		}

		return self::sendMessage($context, $message, $in_queue);
	}

	static public function sendMessage(int $context, Mail_Message $message, bool $in_queue = false): bool
	{
		if (DISABLE_EMAIL) {
			return false;
		}

		$signal = Plugins::fire('email.send.before', true, compact('context', 'message'), ['sent' => null]);

		if ($signal && $signal->isStopped()) {
			return $signal->getOut('sent') ?? true;
		}

		if (SMTP_HOST) {
			static $smtp = null;

			// Re-use SMTP connection in queues
			if (null === $smtp) {
				$const = '\KD2\SMTP::' . strtoupper(SMTP_SECURITY);
				$secure = constant($const);

				$smtp = new SMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, $secure, SMTP_HELO_HOSTNAME);
			}

			try {
				$return = $smtp->send($message);
				// TODO: store return message from SMTP server
			}
			catch (SMTP_Exception $e) {
				// Handle invalid recipients addresses
				if ($r = $e->getRecipient()) {
					if ($e->getCode() >= 500) {
						self::handleManualBounce($r, 'hard', $e->getMessage());
						// Don't retry delivering this email
						return true;
					}
					elseif ($e->getCode() === SMTP::GREYLISTING_CODE) {
						// Resend later (FIXME: only retry for X times)
						return false;
					}
					elseif ($e->getCode() >= 400) {
						self::handleManualBounce($r, 'soft', $e->getMessage());
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
			$message->send();
		}

		Plugins::fire('email.send.after', false, compact('context', 'message'));
		return true;
	}

	/**
	 * Handle a bounce message
	 * @param  string $raw_message Raw MIME message from SMTP
	 */
	static public function handleBounce(string $raw_message): ?array
	{
		$message = new Mail_Message;
		$message->parse($raw_message);

		$return = $message->identifyBounce();
		$address = $return['recipient'] ?? null;

		$signal = Plugins::fire('email.bounce', false, compact('address', 'message', 'return', 'raw_message'));

		if ($signal && $signal->isStopped()) {
			return null;
		}

		if (!$return) {
			return null;
		}

		if ($return['type'] === 'autoreply') {
			// Ignore auto-responders
			return $return;
		}
		elseif ($return['type'] === 'genuine') {
			// Forward emails that are not automatic to the organization email
			$config = Config::getInstance();

			$new = new Mail_Message;
			$new->setHeaders([
				'To'      => $config->org_email,
				'Subject' => 'Réponse à un message que vous avez envoyé',
			]);

			$new->setBody('Veuillez trouver ci-joint une réponse à un message que vous avez envoyé à un de vos membre.');

			$new->attachMessage($message->output());

			self::sendMessage(self::CONTEXT_SYSTEM, $new);
			return $return;
		}
		elseif ($return['type']=== 'permanent') {
			$return['type'] = 'hard';
		}
		elseif ($return['type']=== 'temporary') {
			$return['type'] = 'soft';
		}

		return self::handleManualBounce($return['recipient'], $return['type'], $return['message']);
	}

	static public function handleManualBounce(string $raw_address, string $type, ?string $message): ?array
	{
		$address = self::getOrCreateEmail($raw_address);

		$address->hasBounced($type, $message);
		Plugins::fire('email.bounce.save.before', false, compact('address', 'raw_address', 'type', 'message'));
		$address->save();

		return compact('type', 'message', 'raw_address');
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

		$hash = hash_hmac('sha1', $params[1] . $params[2], SECRET_KEY);

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
		$hash = hash_hmac('sha1', $expiry . $url, SECRET_KEY);

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
