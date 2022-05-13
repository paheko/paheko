<?php

namespace Garradin\Users;

use Garradin\Config;
use Garradin\DB;
use Garradin\Plugin;
use Garradin\Entities\Users\Email;

use const Garradin\{USE_CRON};
use const Garradin\{SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_SECURITY};

use KD2\SMTP;
use KD2\Mail_Message;
use KD2\DB\EntityManager as EM;

class Emails
{
    const CONTEXT_BULK = 1;
    const CONTEXT_PRIVATE = 2;
    const CONTEXT_SYSTEM = 0;

	/**
	 * Seuil à partir duquel on n'essaye plus d'envoyer de message à cette adresse
	 */
	const FAIL_LIMIT = 5;

	/**
	 * Add a message to the sending queue using templates
	 * @param  int          $context
	 * @param  array        $recipients List of recipients, 'From' email address as the key, and an array as a value, that contains variables to be used in the email template
	 * @param  string       $sender
	 * @param  string       $subject
	 * @param  UserTemplate $template
	 * @param  UserTemplate $template_html
	 * @return void
	 */
	static public function queueTemplate(int $context, array $recipients, string $sender, string $subject, UserTemplate $template, ?UserTemplate $template_html): void
	{
		// Remove duplicates
		array_walk($recipients, 'strtolower');
		$recipients = array_unique($recipients);

		$db = DB::getInstance();
		$st = $db->prepare('INSERT INTO emails_queue (sender, subject, recipient, content, content_html, context)
			VALUES (:sender, :subject, :recipient, :content, :content_html, :context);');

		$st->bindValue(':sender', $sender);
		$st->bindValue(':subject', $subject);
		$st->bindValue(':context', $context);

		foreach ($recipients as $to => $variables) {
			// Ignore obviously invalid emails here
			if (self::checkAddress($to)) {
				self::markAddressAsInvalid($to);
				continue;
			}

			// We won't try to reject invalid/optout recipients here,
			// it's done in the queue clearing (more efficient)
			$hash = Email::getHash($to);

			$content = $template->fetch($variables);
			$content_html = $template_html ? $template_html->fetch($variables) : null;

			$st->bindValue(':recipient', $to);
			$st->bindValue(':recipient_hash', $hash);
			$st->bindValue(':content', $content);
			$st->bindValue(':content_html', $content_html);
			$st->execute();

			$st->reset();
			$st->clear();
		}

		Plugin::fireSignal('email.queue.added');

		if (!USE_CRON) {
			self::runQueue();
		}
	}

	/**
	 * Add a message to the sending queue
	 * @param  int          $context
	 * @param  array        $recipients List of recipients emails
	 * @param  string       $sender
	 * @param  string       $subject
	 * @param  UserTemplate $template
	 * @param  UserTemplate $template_html
	 * @return void
	 */
	static public function queue(int $context, array $recipients, ?string $sender, string $subject, string $text): void
	{
		// Remove duplicates
		array_walk($recipients, fn($a) => strtolower($a));
		$recipients = array_unique($recipients);

		$db = DB::getInstance();
		$st = $db->prepare('INSERT INTO emails_queue (sender, subject, recipient, recipient_hash, content, context)
			VALUES (:sender, :subject, :recipient, :recipient_hash, :content, :context);');

		foreach ($recipients as $to) {
			// Ignore obviously invalid emails here
			if (!self::checkAddress($to)) {
				self::markAddressAsInvalid($to);
				continue;
			}

			// We won't try to reject invalid/optout recipients here,
			// it's done in the queue clearing (more efficient)
			$hash = Email::getHash($to);

			$st->bindValue(':sender', $sender);
			$st->bindValue(':subject', $subject);
			$st->bindValue(':context', $context);
			$st->bindValue(':recipient', $to);
			$st->bindValue(':recipient_hash', $hash);
			$st->bindValue(':content', $text);
			$st->execute();

			$st->reset();
			$st->clear();
		}

		Plugin::fireSignal('email.queue.added');

		// If no crontab is used, then the queue should be run now
		if (!USE_CRON) {
			self::runQueue();
		}
	}

	static public function getEmailFromOptout(string $code): ?Email
	{
		$hash = base64_decode(str_pad(strtr($code, '-_', '+/'), strlen($code) % 4, '=', STR_PAD_RIGHT));

		if (!$hash) {
			return null;
		}

		$hash = bin2hex($hash);
		return EM::findOne(Email::class, 'SELECT * FROM @TABLE WHERE hash = ?;', $hash);
	}

	static public function markAddressAsInvalid(string $address): void
	{
		$e = self::getEmail($address);

		if (!$e) {
			return;
		}

		$e->set('invalid', true);
		$e->save();
	}

	static public function getEmail(string $address): ?Email
	{
		return EM::findOne(Email::class, 'SELECT * FROM @TABLE WHERE hash = ?;', Email::getHash($address));
	}

	/**
	 * Vérifie qu'une adresse est valide
	 * @param  string $address
	 * @return boolean FALSE si l'adresse est invalide (syntaxe)
	 */
	static public function checkAddress(string $address): bool
	{
		$address = strtolower(trim($address));

		// Ce domaine n'existe pas (MX inexistant), erreur de saisie courante
		if (false !== stristr($address, '@gmail.fr'))
		{
			return false;
		}

		if (!SMTP::checkEmailIsValid($address, true))
		{
			return false;
		}

		return true;
	}

	static public function runQueue()
	{
		$db = DB::getInstance();

		$queue = self::listQueueAndMarkAsSending();
		$ids = [];

		// listQueue nettoie déjà la queue
		foreach ($queue as $row) {
			// Don't send emails to opt-out address, unless it's a password reminder
			if ($row->context != self::CONTEXT_SYSTEM && $row->optout) {
				self::deleteFromQueue($row->id);
				continue;
			}

			$headers = [
				'From' => $row->sender,
				'To' => $row->recipient,
				'Subject' => $row->subject,
			];

			self::send($row->context, $row->recipient_hash, $headers, $row->content, $row->content_html);
			$ids[] = $row->id;
		}

		// Update emails list and send count
		// then delete messages from queue
		$db->exec(sprintf('
		BEGIN;
			UPDATE emails_queue SET sending = 2 WHERE %s;
			INSERT OR IGNORE INTO %s (hash) SELECT recipient_hash FROM emails_queue WHERE sending = 2;
			UPDATE %2$s SET sent_count = sent_count + 1 WHERE hash IN (SELECT recipient_hash FROM emails_queue WHERE sending = 2);
			DELETE FROM emails_queue WHERE sending = 2;
		END;', $db->where('id', $ids), Email::TABLE));
	}

	/**
	 * Liste la file d'attente et marque les éléments listés comme "en cours de traitement"
	 * @return array
	 */
	static protected function listQueueAndMarkAsSending()
	{
		$queue = self::listQueue();

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
	 * Renvoie la liste des emails en attente d'envoi dans la queue,
	 * sauf ceux qui correspondent à des adresses bloquées.
	 *
	 * Ne pas utiliser pour les envois, sinon risque d'avoir plusieurs tâches
	 * qui envoient le même email !
	 * @return array
	 */
	static protected function listQueue(): array
	{
		// Nettoyage de la queue déjà
		self::purgeQueueFromRejected();
		self::resetFailed();
		return DB::getInstance()->get('SELECT q.*, e.optout, e.verified
			FROM emails_queue q
			LEFT JOIN emails e ON e.hash = q.recipient_hash
			WHERE q.sending = 0;');
	}

	static public function listRejectedUsers(): array
	{
		$sql = sprintf('SELECT e.*, u.email, u.id, u.%s AS identity
			FROMS emails e
			LEFT JOIN membres u ON u.email IS NOT NULL AND u.email != \'\' AND e.hash = email_hash(u.email)
			WHERE e.optout = 1 OR e.invalid = 1 OR e.fail_count >= %d;',
			$id_field,
			self::FAIL_LIMIT
		);

		return DB::getInstance()->get($sql);
	}

	/**
	 * Supprime de la queue les messages liés à des adresses invalides
	 * ou qui ne souhaitent plus recevoir de message
	 * @return boolean
	 */
	static protected function purgeQueueFromRejected(): void
	{
		DB::getInstance()->delete('emails_queue',
			'recipient_hash IN (SELECT hash FROM emails WHERE invalid = 1 OR fail_count >= ?)',
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

	static protected function send(int $context, string $recipient_hash, array $headers, string $content, ?string $content_html): void
	{
		$config = Config::getInstance();

		$message = new Mail_Message;
		$message->setHeaders($headers);

		if (!$message->getFrom()) {
			$message->setHeader('From', sprintf('"%s" <%s>', $config->nom_asso, $config->email_asso));
		}

		$message->setMessageId();

		// Append unsubscribe, except for password reminders
		if ($context != self::CONTEXT_SYSTEM) {
			$url = Email::getOptoutURL($recipient_hash);

			// RFC 8058
			$message->setHeader('List-Unsubscribe', sprintf('<%s>', $url));
			$message->setHeader('List-Unsubscribe-Post', 'Unsubscribe=Yes');

			$optout_text = "Vous recevez ce message car vous êtes inscrit comme membre de\nl'association.\n"
				. "Pour ne plus jamais recevoir de message de notre part cliquez sur le lien suivant :\n";

			$content .= "\n\n-- \n" . $optout_text . $url;

			if (null !== $content_html) {
				$optout_text = '<hr /><p style="color: #000; background: #fff">' . nl2br(htmlspecialchars($optout_text));
				$optout_text.= sprintf('<a href="%s" style="color: blue; text-decoration: underline; padding: 5px; border-radius: 5px; background: #eee;">Me désinscrire</a>', $url);

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

		$email_sent_via_plugin = Plugin::fireSignal('email.send.before', compact('context', 'message', 'content', 'content_html'));

		if ($email_sent_via_plugin) {
			return;
		}

		if (SMTP_HOST) {
			$const = '\KD2\SMTP::' . strtoupper(SMTP_SECURITY);
			$secure = constant($const);

			$to = $message->getTo()[0];
			$from = $message->getFrom()[0];

			$smtp = new SMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, $secure);
			$smtp->rawSend($from, $to, $message->output());
		}
		else {
			$message->send();
		}

		Plugin::fireSignal('email.send.after', compact('context', 'message', 'content', 'content_html'));
	}

	/**
	 * Handle a bounce message
	 * @param  string $raw_message Raw MIME message from SMTP
	 */
	static public function handleBounce(string $raw_message): void
	{
		$message = new Mail_Message;
		$message->parse($raw_message);

		$return = $message->identifyBounce();

		if ($return['type'] == 'autoreply') {
			// Ignore auto-responders
			return;
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

			self::send(self::CONTEXT_SYSTEM, $new->output());
			return;
		}

		$email = self::getEmail($return['recipient']);

		if (!$email) {
			return;
		}

		$email->hasFailed($return);
		$email->save();
	}
}
