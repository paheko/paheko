<?php

namespace Garradin\Users;

use Garradin\Config;
use Garradin\CSV;
use Garradin\DB;
use Garradin\DynamicList;
use Garradin\Plugin;
use Garradin\UserException;
use Garradin\Entities\Users\Email;
use Garradin\UserTemplate\UserTemplate;
use Garradin\Web\Render\Render;
use Garradin\Web\Skeleton;

use const Garradin\{USE_CRON, MAIL_RETURN_PATH};
use const Garradin\{SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_SECURITY};

use KD2\SMTP;
use KD2\Mail_Message;
use KD2\DB\EntityManager as EM;

class Emails
{
	const RENDER_FORMATS = [
		null => 'Texte brut',
		Render::FORMAT_SKRIV => 'SkrivML',
		Render::FORMAT_MARKDOWN => 'MarkDown',
	];

	/**
	 * Email sending contexts
	 */
	const CONTEXT_BULK = 1;
	const CONTEXT_PRIVATE = 2;
	const CONTEXT_SYSTEM = 0;

	/**
	 * When we reach that number of fails, the address is treated as permanently invalid, unless reset by a verification.
	 */
	const FAIL_LIMIT = 5;

	/**
	 * Add a message to the sending queue using templates
	 * @param  int          $context
	 * @param  array        $recipients List of recipients, 'From' email address as the key, and an array as a value, that contains variables to be used in the email template
	 * @param  string       $sender
	 * @param  string       $subject
	 * @param  UserTemplate|string $content
	 * @return void
	 */
	static public function queue(int $context, array $recipients, ?string $sender, string $subject, $content, ?string $render = null): void
	{
		// Remove duplicates due to case changes
		$recipients = array_change_key_case($recipients, CASE_LOWER);

		if (Plugin::fireSignal('email.queue.before', compact('context', 'recipients', 'sender', 'subject', 'content', 'render'))) {
			// queue handling was done by a plugin
			return;
		}

		$template = ($content instanceof UserTemplate) ? $content : null;
		$skel = null;
		$content_html = null;

		if ($template) {
			$template->toggleSafeMode(true);
		}

		$db = DB::getInstance();
		$db->begin();
		$st = $db->prepare('INSERT INTO emails_queue (sender, subject, recipient, recipient_hash, content, content_html, context)
			VALUES (:sender, :subject, :recipient, :recipient_hash, :content, :content_html, :context);');

		if ($render) {
			$skel = new Skeleton('email.html');
		}

		foreach ($recipients as $to => $variables) {
			// Ignore invalid addresses
			if (!preg_match('/.+@.+\..+$/', $to)) {
				continue;
			}

			// We won't try to reject invalid/optout recipients here,
			// it's done in the queue clearing (more efficient)
			$hash = Email::getHash($to);

			$content_html = null;

			if ($template) {
				$template->assignArray((array) $variables, null, false);

				// Disable HTML escaping for plaintext emails
				$template->setEscapeDefault(null);
				$content = $template->fetch();

				if ($render) {
					$content_html = $template->fetch();
				}
			}

			if ($render) {
				$content_html = Render::render($render, null, $content_html ?? $content);
			}

			if ($content_html) {
				// Wrap HTML content in the email skeleton
				$content_html = $skel->fetch([
					'html'      => $content_html,
					'recipient' => $to,
					'data'      => $variables,
					'context'   => $context,
					'from'      => $sender,
				]);
			}

			if (Plugin::fireSignal('email.queue.insert', compact('context', 'to', 'sender', 'subject', 'content', 'render', 'hash', 'content_html'))) {
				// queue insert was done by a plugin
				continue;
			}

			$st->bindValue(':sender', $sender);
			$st->bindValue(':subject', $subject);
			$st->bindValue(':context', $context);
			$st->bindValue(':recipient', $to);
			$st->bindValue(':recipient_hash', $hash);
			$st->bindValue(':content', $content);
			$st->bindValue(':content_html', $content_html);
			$st->execute();

			$st->reset();
			$st->clear();
		}

		$db->commit();

		if (Plugin::fireSignal('email.queue.after', compact('context', 'recipients', 'sender', 'subject', 'content', 'render'))) {
			return;
		}

		// If no crontab is used, then the queue should be run now
		if (!USE_CRON) {
			self::runQueue();
		}
		// Always send system emails right away
		elseif ($context == self::CONTEXT_SYSTEM) {
			self::runQueue(self::CONTEXT_SYSTEM);
		}
	}

	/**
	 * Return an Email entity from the optout code
	 */
	static public function getEmailFromOptout(string $code): ?Email
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
		$address = strtolower($address);
		$e = self::getEmail($address);

		if (!$e) {
			$e = new Email;
			$e->added = new \DateTime;
			$e->hash = $e::getHash($address);
			$e->validate($address);
			$e->save();
		}

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

		// listQueue nettoie déjà la queue
		foreach ($queue as $row) {
			// We allow system emails to be sent to invalid addresses after a while, and to optout addresses all the time
			if ($row->optout || $row->invalid || $row->fail_count >= self::FAIL_LIMIT) {
				if ($row->context != self::CONTEXT_SYSTEM || (!$row->optout && $row->last_sent > $limit_time)) {
					self::deleteFromQueue($row->id);
					continue;
				}
			}

			// Create email address in database
			if (!$row->email_hash) {
				$email = self::getOrCreateEmail($row->recipient);

				if (!$email->canSend()) {
					// Email address is invalid, skip
					self::deleteFromQueue($row->id);
					continue;
				}
			}

			$headers = [
				'From' => $row->sender,
				'To' => $row->recipient,
				'Subject' => $row->subject,
			];

			try {
				self::send($row->context, $row->recipient_hash, $headers, $row->content, $row->content_html);
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
		$db->exec(sprintf('
		BEGIN;
			UPDATE emails_queue SET sending = 2 WHERE %s;
			INSERT OR IGNORE INTO %s (hash) SELECT recipient_hash FROM emails_queue WHERE sending = 2;
			UPDATE %2$s SET sent_count = sent_count + 1, last_sent = datetime()
				WHERE hash IN (SELECT recipient_hash FROM emails_queue WHERE sending = 2);
			DELETE FROM emails_queue WHERE sending = 2;
		END;', $db->where('id', $ids), Email::TABLE));

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

		return DB::getInstance()->get(sprintf('SELECT q.*, e.optout, e.verified, e.hash AS email_hash,
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
	static protected function resetFailed(): void
	{
		$sql = 'UPDATE emails_queue SET sending = 0, sending_started = NULL
			WHERE sending = 1 AND sending_started < datetime(\'now\', \'-3 hours\');';
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

	static public function listRejectedUsers(): DynamicList
	{
		$db = DB::getInstance();

		$columns = [
			'identity' => [
				'label' => 'Membre',
				'select' => 'u.' . $db->quoteIdentifier(Config::getInstance()->champ_identite),
			],
			'email' => [
				'label' => 'Adresse',
				'select' => 'u.email',
			],
			'user_id' => [
				'select' => 'u.id',
			],
			'hash' => [
			],
			'status' => [
				'label' => 'Statut',
				'select' => sprintf('CASE
					WHEN e.optout = 1 THEN \'Désinscription\'
					WHEN e.invalid = 1 THEN \'Invalide\'
					WHEN e.fail_count >= %d THEN \'Trop d\'\'erreurs\'
					WHEN e.verified = 1 THEN \'Vérifiée\'
					ELSE \'\'
					END', self::FAIL_LIMIT),
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
			'optout' => [],
			'fail_count' => [],
		];

		$tables = 'emails e
			INNER JOIN membres u ON u.email IS NOT NULL AND u.email != \'\' AND e.hash = email_hash(u.email)';

		$conditions = sprintf('e.optout = 1 OR e.invalid = 1 OR e.fail_count >= %d', self::FAIL_LIMIT);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('last_sent', true);
		$list->setModifier(function (&$row) {
			$row->last_sent = $row->last_sent ? new \DateTime($row->last_sent) : null;
		});
		return $list;
	}

	static protected function send(int $context, string $recipient_hash, array $headers, string $content, ?string $content_html): void
	{
		$message = new Mail_Message;
		$message->setHeaders($headers);

		if (!$message->getFrom()) {
			$message->setHeader('From', self::getFromHeader());
		}

		$message->setMessageId();

		// Append unsubscribe, except for password reminders
		if ($context != self::CONTEXT_SYSTEM) {
			$url = Email::getOptoutURL($recipient_hash);

			// RFC 8058
			$message->setHeader('List-Unsubscribe', sprintf('<%s>', $url));
			$message->setHeader('List-Unsubscribe-Post', 'Unsubscribe=Yes');

			$optout_text = "Vous recevez ce message car vous êtes dans nos contacts.\n"
				. "Pour ne plus jamais recevoir de message de notre part cliquez ici :\n";

			$content .= "\n\n-- \n" . $optout_text . $url;

			if (null !== $content_html) {
				$optout_text = '<hr style="border-top: 2px solid #999; background: none;" /><p style="color: #000; background: #fff; padding: 10px; text-align: center; font-size: 9pt">' . nl2br(htmlspecialchars($optout_text));
				$optout_text.= sprintf('<br /><a href="%s" style="color: blue; text-decoration: underline; padding: 5px; border-radius: 5px; background: #ddd;">Me désinscrire</a></p>', $url);

				if (stripos($content_html, '</body>') !== false) {
					$content_html = str_ireplace('</body>', $optout_text . '</body>', $content_html);
				}
				else {
					$content_html .= $optout_text;
				}
			}
		}

		$message->setBody($content);

		if (null !== $content_html) {
			$message->addPart('text/html', $content_html);
		}

		$config = Config::getInstance();
		$message->setHeader('Return-Path', MAIL_RETURN_PATH ?? $config->email_asso);
		$message->setHeader('X-Auto-Response-Suppress', 'All'); // This is to avoid getting auto-replies from Exchange servers

		self::sendMessage($context, $message);
	}

	static public function sendMessage(int $context, Mail_Message $message)
	{
		$email_sent_via_plugin = Plugin::fireSignal('email.send.before', compact('context', 'message'));

		if ($email_sent_via_plugin) {
			return;
		}

		if (SMTP_HOST) {
			$const = '\KD2\SMTP::' . strtoupper(SMTP_SECURITY);
			$secure = constant($const);

			$smtp = new SMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, $secure);
			$smtp->send($message);
		}
		else {
			$message->send();
		}

		Plugin::fireSignal('email.send.after', compact('context', 'message'));
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

		if (Plugin::fireSignal('email.bounce', compact('message', 'return', 'raw_message'))) {
			return null;
		}

		if (!$return) {
			return null;
		}

		if ($return['type'] == 'autoreply') {
			// Ignore auto-responders
			return $return;
		}
		elseif ($return['type'] == 'genuine') {
			// Forward emails that are not automatic to the organization email
			$config = Config::getInstance();

			$new = new Mail_Message;
			$new->setHeaders([
				'To'      => $config->email_asso,
				'Subject' => 'Réponse à un message que vous avez envoyé',
			]);

			$new->setBody('Veuillez trouver ci-joint une réponse à un message que vous avez envoyé à un de vos membre.');

			$new->attachMessage($message->output());

			self::sendMessage(self::CONTEXT_SYSTEM, $new);
			return $return;
		}

		return self::handleManualBounce($return['recipient'], $return['type'], $return['message']);
	}

	static public function handleManualBounce(string $recipient, string $type, ?string $message): ?array
	{
		$return = compact('recipient', 'type', 'message');
		$email = self::getOrCreateEmail($return['recipient']);

		if (!$email) {
			return null;
		}

		Plugin::fireSignal('email.bounce', compact('email', 'return'));
		$email->hasFailed($return);
		$email->save();

		return $return;
	}

	/**
	 * Create a mass mailing
	 */
	static public function createMailing(array $recipients, string $subject, string $message, bool $send_copy, ?string $render): \stdClass
	{
		$list = [];

		foreach ($recipients as $recipient) {
			if (empty($recipient->email)) {
				continue;
			}

			$list[$recipient->email] = $recipient;
		}

		if (!count($list)) {
			throw new UserException('Aucun destinataire de la liste ne possède d\'adresse email.');
		}

		$html = null;
		$tpl = null;

		$random = array_rand($list);

		if (false !== strpos($message, '{{')) {
			$tpl = new UserTemplate;
			$tpl->setCode($message);
			$tpl->toggleSafeMode(true);
			$tpl->assignArray((array)$list[$random], null, false);
			$tpl->setEscapeDefault(null);

			try {
				if (!$render) {
					// Disable HTML escaping for plaintext emails
					$message = $tpl->fetch();
				}
				else {
					$html = $tpl->fetch();
				}
			}
			catch (\KD2\Brindille_Exception $e) {
				throw new UserException('Erreur de syntaxe dans le corps du message :' . PHP_EOL . $e->getPrevious()->getMessage(), 0, $e);
			}
		}

		if ($render) {
			$html = Render::render($render, null, $html ?? $message);
		}
		elseif (null !== $html) {
			$html = '<pre>' . $html . '</pre>';
		}
		else {
			$html = '<pre>' . htmlspecialchars(wordwrap($message)) . '</pre>';
		}

		$recipients = $list;

		$config = Config::getInstance();
		$sender = sprintf('"%s" <%s>', $config->nom_asso, $config->email_asso);
		$message = (object) compact('recipients', 'subject', 'message', 'sender', 'tpl', 'send_copy', 'render');
		$message->preview = (object) [
			'to'      => $random,
			// Not required to be a valid From header, this is just a preview
			'from'    => $sender,
			'subject' => $subject,
			'html'    => $html,
		];

		return $message;
	}

	static public function getFromHeader(string $name = null, string $email = null): string
	{
		$config = Config::getInstance();

		if (null === $name) {
			$name = $config->nom_asso;
		}
		if (null === $email) {
			$email = $config->email_asso;
		}

		$name = str_replace('"', '\\"', $name);
		$name = str_replace(',', '', $name); // Remove commas

		return sprintf('"%s" <%s>', $name, $email);
	}

	/**
	 * Send a mass mailing
	 */
	static public function sendMailing(\stdClass $mailing): void
	{
		if (!isset($mailing->recipients, $mailing->subject, $mailing->message, $mailing->send_copy)) {
			throw new \InvalidArgumentException('Invalid $mailing object');
		}

		if (!count($mailing->recipients)) {
			throw new UserException('Aucun destinataire de la liste ne possède d\'adresse email.');
		}

		Emails::queue(Emails::CONTEXT_BULK,
			$mailing->recipients,
			null, // Default sender
			$mailing->subject,
			$mailing->tpl ?? $mailing->message,
			$mailing->render ?? null
		);

		if ($mailing->send_copy)
		{
			$config = Config::getInstance();
			Emails::queue(Emails::CONTEXT_BULK, [$config->get('email_asso') => null], null, $mailing->subject, $mailing->message);
		}
	}

	static public function exportMailing(string $format, \stdClass $mailing): void
	{
		$rows = $mailing->recipients;
		$id_field = Config::getInstance()->get('champ_identite');

		foreach ($rows as $key => &$row) {
			$row = [$key, $row->$id_field ?? ''];
		}

		unset($row);

		CSV::export($format, 'Destinataires message collectif', $rows, ['Adresse e-mail', 'Identité']);
	}
}
