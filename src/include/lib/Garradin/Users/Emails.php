<?php

namespace Garradin\Users;

use Garradin\Entities\Users\Email;

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
	 * Add a message to the sending queue
	 * @param  int          $context
	 * @param  array        $recipients List of recipients, 'From' email address as the key, and an array as a value, that contains variables to be used in the email template
	 * @param  string       $sender
	 * @param  string       $subject
	 * @param  UserTemplate $template
	 * @param  UserTemplate $template_html
	 * @return void
	 */
	static public function queue(int $context, array $recipients, string $sender, string $subject, UserTemplate $template, ?UserTemplate $template_html): void
	{
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

			$content .= "Vous recevez ce message car vous êtes inscrit comme membre de\nl'association.\n";
			$content .= "Pour ne plus jamais recevoir de message de notre part cliquez sur le lien suivant :\n";
			$content .= self::getOptoutURL(null, $hash);

			$st->bindValue(':recipient', $to);
			$st->bindValue(':recipient_hash', $hash);
			$st->bindValue(':content', $content);
			$st->bindValue(':content_html', $content_html);
			$st->execute();

			$st->reset();
			$st->clear();
		}

		Plugin::fireSignal('email.queue.added');
	}

	static public function getOptoutURL(?string $email, ?string $hash = null): string
	{
		$hash = $hash ?? Email::getHash($email);
		$hash = gzdeflate($hash, 9);
		$hash = base64_encode($hash);
		// Make base64 hash valid for URLs
		$hash = rtrim(strtr($hash, '+/', '-_'), '=');
		return sprintf('%s?un=%s', WWW_URL, $hash);
	}

	static public function getEmailEntityFromOptout(string $code): ?Email
	{
		$hash = base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
		$hash = gzinflate($hash);
		return EM::findOne(Email::class, 'SELECT * FROM @TABLE WHERE hash = ?;', $hash);
	}

	static public function markAddressAsInvalid(string $address): void
	{
		$e = self::getEmailEntity($address);

		if (!$e) {
			return;
		}

		$e->invalid = true;
		$e->save();
	}

	static public function getEmailEntity(string $address): ?Email
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

		$queue = $this->listQueueAndMarkAsSending();

		// listQueue nettoie déjà la queue
		foreach ($queue as $row) {
			// Don't send emails to opt-out address, unless it's a password reminder
			if ($row->context != self::CONTEXT_SYSTEM && $row->optout) {
				$this->deleteFromQueue($row->id);
				continue;
			}

			$msg = new Mail_Message;
			$msg->setHeader('From', $row->sender);
			$msg->setHeader('To', $row->recipient);
			// RFC 8058
			$msg->setHeader('List-Unsubscribe', sprintf('<%s>', self::getOptoutURL(null, $row->recipient_hash)));
			$msg->setHeader('List-Unsubscribe-Post', 'Unsubscribe=Yes');
			self::send($row->recipient, $row->from_name, $row->from_email, $row->subject, $row->content);

			$this->deleteFromQueue($row->id);
		}
	}

	/**
	 * Liste la file d'attente et marque les éléments listés comme "en cours de traitement"
	 * @return array
	 */
	static protected function listQueueAndMarkAsSending()
	{
		$queue = $this->listQueue();

		if (!count($queue)) {
			return $queue;
		}

		$ids = [];

		foreach ($queue as $row) {
			$ids[] = $row->id;
		}

		$db = DB::getInstance();
		$db->update('emails_queue', ['status' => self::STATUS_SENDING, 'status_changed' => new \DateTime], $db->where('id', $ids));

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
	protected function listQueue(): array
	{
		// Nettoyage de la queue déjà
		$this->purgeQueueFromRejected();
		$this->resetFailed();
		return DB::getInstance()->get('SELECT q.*, e.optout, e.verified
			FROM emails_queue q
			LEFT JOIN emails e ON e.hash = q.recipient_hash
			WHERE q.sending = 0;');
	}


	/**
	 * Supprime de la queue les messages liés à des adresses invalides
	 * ou qui ne souhaitent plus recevoir de message
	 * @return boolean
	 */
	public function purgeQueueFromRejected(): void
	{
		DB::getInstance()->delete('emails_queue',
			'recipient_hash IN (SELECT hash FROM emails WHERE invalid = 1 OR fail_count >= ?)',
			self::FAIL_LIMIT);
	}

	/**
	 * If emails have been marked as sending but sending failed, mark them for resend after a while
	 */
	public function resetFailed(): void
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
	public function deleteFromQueue($id)
	{
		return DB::getInstance()->delete('emails_queue', 'id = ?', (int)$id);
	}

	static public function send(int $context, Mail_Message $message): bool
	{
		$config = Config::getInstance();

		if (!$message->getFrom()) {
			$message->setHeader('From', sprintf('"%s" <%s>', $config->nom_asso, $config->email_asso));
		}

		$email_sent_via_plugin = Plugin::fireSignal('email.send.before', compact('context', 'message'));

		if ($email_sent_via_plugin) {
			return true;
		}

		if (SMTP_HOST) {
			$const = '\KD2\SMTP::' . strtoupper(SMTP_SECURITY);
			$secure = constant($const);

			$to = $message->getTo();
			$from = $message->getFrom()[0];

			$smtp = new SMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, $secure);
			return $smtp->rawSend($from, $to, $message->output());
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
	static public function handleBounce(string $raw_message): void
	{
		$msg = new Mail_Message;
		$msg->parse($raw_message);

		$return = $msg->identifyBounce();

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
				'From'    => sprintf('"%s" <%s>', $config->nom_asso, $config->email_asso),
				'Subject' => 'Réponse à un message que vous avez envoyé',
			]);

			$new->setBody('Veuillez trouver ci-joint une réponse à un message que vous avez envoyé à un de vos membre.');

			$new->attachMessage($msg->output());

			self::send(self::CONTEXT_SYSTEM, $new->output());
			return;
		}

		$email = $this->getEmailEntity($return['recipient']);

		if (!$email) {
			return;
		}

		$email->hasFailed($return);
		$email->save();
	}
}
