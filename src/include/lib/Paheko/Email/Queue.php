<?php

namespace Paheko\Email;

use Paheko\Entities\Email\Message;

use Paheko\DB;
use Paheko\DynamicList;

class Queue
{
	static public function append(int $context, string $email, array $data = [])
	{

	}

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

		$is_system = $context === Message::CONTEXT_SYSTEM;
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
			$recipient_hash = Addresses::hash($recipient);

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
			self::run();
		}
		// Always send system emails right away
		elseif ($is_system) {
			self::run(Message::CONTEXT_SYSTEM);
		}
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

			$db->exec(sprintf('UPDATE emails_queue SET status = %d WHERE %s;', Message::STATUS_SENT, $db->where('id', $ids)));
			$ids = [];
		};

		$limit_time = strtotime('1 month ago');
		$count = 0;
		$all_attachments = [];

		// listQueue nettoie déjà la queue
		foreach ($queue as $row) {
			// We allow system emails to be sent to invalid addresses after a while, and to optout addresses all the time
			if ($row->optout || $row->invalid || $row->fail_count >= Message::FAIL_LIMIT) {
				if ($row->context != Message::CONTEXT_SYSTEM || (!$row->optout && $row->last_sent > $limit_time)) {
					self::delete($row->id);
					continue;
				}
			}

			// Create email address in database
			if (!$row->email_hash) {
				$email = Addresses::getOrCreate($row->recipient);

				if (!$email->canSend()) {
					// Email address is invalid, skip
					self::delete($row->id);
					continue;
				}
			}

			$headers = [
				'From'    => $row->sender,
				'To'      => $row->recipient,
				'Subject' => $row->subject,
			];

			try {
				$attachments = $db->getAssoc('SELECT id, path FROM emails_queue_attachments WHERE id_queue = ?;', $row->id);
				$all_attachments = array_merge($all_attachments, $attachments);
				$sent = self::send($row->context, $row->recipient_hash, $headers, $row->content, $row->content_html, $row->recipient_pgp_key, $attachments);

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
			UPDATE emails_queue SET status = %d WHERE %s;
			INSERT OR IGNORE INTO %s (hash) SELECT recipient_hash FROM emails_queue WHERE status = %1$d;
			UPDATE %3$s SET sent_count = sent_count + 1, last_sent = datetime()
				WHERE hash IN (SELECT recipient_hash FROM emails_queue WHERE status = %1$d);
			DELETE FROM emails_queue WHERE status = %1$d;',
			Message::STATUS_SENT,
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
		$queue = self::list($context);

		if (!count($queue)) {
			return $queue;
		}

		$ids = [];

		foreach ($queue as $row) {
			$ids[] = $row->id;
		}

		$db = DB::getInstance();
		$db->update('emails_queue', ['status' => Message::STATUS_SENDING, 'sending_started' => new \DateTime], $db->where('id', $ids));

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
	static protected function list(?int $context = null): array
	{
		// Clean-up the queue from reject emails
		self::removeRejectedRecipients();

		// Reset messages that failed during the queue run
		self::resetFailed();

		$condition = null === $context ? '' : sprintf(' AND context = %d', $context);

		return DB::getInstance()->get(sprintf('SELECT q.*, a.optout, a.verified, a.hash AS email_hash,
				a.invalid, a.fail_count, strftime(\'%%s\', a.last_sent) AS last_sent
			FROM emails_queue q
			LEFT JOIN emails_addresses a ON a.hash = q.recipient_hash
			WHERE q.status = %d %s;', Message::STATUS_WAITING, $condition));
	}

	/**
	 * Supprime de la queue les messages liés à des adresses invalides
	 * ou qui ne souhaitent plus recevoir de message
	 * @return boolean
	 */
	static protected function removeRejectedRecipients(): void
	{
		DB::getInstance()->delete('emails_queue',
			'recipient_hash IN (SELECT hash FROM emails_addresses WHERE (invalid = 1 OR fail_count >= ?)
			AND last_sent >= datetime(\'now\', \'-1 month\'));',
			self::FAIL_LIMIT);
	}

	/**
	 * If emails have been marked as sending but sending failed, mark them for resend after a while
	 */
	static protected function resetFailed(): void
	{
		$sql = 'UPDATE emails_queue SET status = %d, sending_started = NULL
			WHERE status = %d AND sending_started < datetime(\'now\', \'-3 hours\');';
		$sql = sprintf($sql, Message::STATUS_WAITING, Message::STATUS_SENDING);
		DB::getInstance()->exec($sql);
	}

	/**
	 * Supprime un message de la queue d'envoi
	 */
	static protected function delete(int $id): bool
	{
		return DB::getInstance()->delete('emails_queue', 'id = ?', (int)$id);
	}

	static public function count(): int
	{
		return DB::getInstance()->count('emails_queue', 'status = ' . Message::STATUS_WAITING);
	}

	static public function getList(): DynamicList
	{
		$columns = [
			'id' => [],
			'context' => [
				'label' => 'Contexte',
			],
			'status' => [
				'label' => 'Statut',
				'order' => 'status %s, id %1$s',
			],
			'sender' => [
				'label' => 'Expéditeur',
			],
			'recipient' => [
				'label' => 'Destinataire',
			],
			'subject' => [
				'label' => 'Sujet',
			],
		];

		$list = new DynamicList($columns, 'emails_queue');
		$list->orderBy('status', true);
		return $list;
	}

	static public function createMessage(int $context, ?string $subject = null, ?string $body = null, ?string $html_body = null): Message
	{
		$msg = new Message;
		$msg->set('context', $context);

		if (null !== $subject) {
			$msg->set('subject', $subject);
		}

		if (null !== $body) {
			$msg->set('body', $body);
		}

		if (null !== $html_body) {
			$msg->set('html_body', $html_body);
		}

		return $msg;
	}
}
