<?php

namespace Paheko\Entities\Email;

use Paheko\Config;
use Paheko\CSV;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Entity;
use Paheko\Log;
use Paheko\UserException;
use Paheko\Email\Emails;
use Paheko\Users\DynamicFields;
use Paheko\Users\Users;
use Paheko\UserTemplate\UserTemplate;
use Paheko\Web\Render\Render;

use DateTime;
use stdClass;

class Mailing extends Entity
{
	const TABLE = 'mailings';
	const NAME = 'Message collectif';
	const PRIVATE_URL = '!users/mailing/details.php?id=%d';

	const TARGETS_TYPES = [
		'all'      => 'Tous les membres (sauf catégories cachées)',
		'field'    => 'Champ de la fiche membre',
		'category' => 'Catégorie',
		'service'  => 'Inscrits à jour d\'une activité',
		'search'   => 'Recherche enregistrée',
	];

	protected ?int $id = null;
	protected string $subject;
	protected ?string $body;

	/**
	 * We need to store these in order to have opt-out per-target
	 */
	protected ?string $target_type;
	protected ?string $target_value;
	protected ?string $target_label;

	/**
	 * Leave sender name and email NULL to use org name + email
	 */
	protected ?string $sender_name;
	protected ?string $sender_email;

	/**
	 * NULL when the mailing has not been sent yet
	 */
	protected ?DateTime $sent;

	/**
	 * TRUE when the list of recipients has been anonymized
	 * @var boolean
	 */
	protected bool $anonymous = false;

	public function selfCheck(): void
	{
		parent::selfCheck();

		$this->assert(trim($this->subject) !== '', 'Le sujet ne peut rester vide.');
		$this->assert(!isset($this->body) || trim($this->body) !== '', 'Le corps du message ne peut rester vide.');

		if (isset($this->sender_name) || isset($this->sender_email)) {
			$this->assert(trim($this->sender_name) !== '', 'Le nom d\'expéditeur est vide.');
			$this->assert(trim($this->sender_email) !== '', 'L\'adresse e-mail de l\'expéditeur est manquante.');
			$this->assert(Email::isAddressValid($this->sender_email), 'L\'adresse e-mail de l\'expéditeur est invalide.');
		}
	}

	public function getTargetTypeLabel(): string
	{
		return self::TARGETS_TYPES[$this->target_type] ?? '';
	}

	public function populate(): void
	{
		if ($this->target_type !== 'all' && empty($this->target_value)) {
			throw new \InvalidArgumentException('Missing target ID');
		}

		if ($this->target_type === 'field') {
			$recipients = Users::iterateEmailsByField($this->target_value, true);
		}
		elseif ($this->target_type === 'all') {
			$recipients = Users::iterateEmailsByCategory(null);
		}
		elseif ($this->target_type === 'category') {
			$recipients = Users::iterateEmailsByCategory((int) $this->target_value);
		}
		elseif ($this->target_type === 'search') {
			$recipients = Users::iterateEmailsBySearch((int) $this->target_value);
		}
		elseif ($this->target_type === 'service') {
			$recipients = Users::iterateEmailsByActiveService((int) $this->target_value);
		}
		else {
			throw new \InvalidArgumentException('Invalid target');
		}

		$db = DB::getInstance();
		$db->begin();
		$count = 0;

		foreach ($recipients as $email => $data) {
			// Ignore empty emails, normally NULL emails are already discarded in WHERE clauses
			// But, just to be sure
			if (empty($email)) {
				continue;
			}

			$this->addRecipient($email, $data);
			$count++;
		}

		if (!$count) {
			$db->rollback();
			throw new UserException('La liste de destinataires sélectionnée ne comporte aucun membre, ou aucun avec une adresse e-mail renseignée.');
		}

		$this->cleanupRecipients();

		$db->commit();
	}

	/**
	 * Remove opt-out recipients from list
	 */
	public function cleanupRecipients(): void
	{

	}

	public function addRecipient(string $email, $data = null): void
	{
		if (!$this->exists()) {
			throw new \LogicException('Mailing does not exist');
		}

		$email = strtolower(trim($email));
		$e = Emails::getEmail($email);

		if ($e && !$e->canSend()) {
			$data = null;
		}
		else {
			try {
				// Validate e-mail address, but not MX (quick check)
				Email::validateAddress($email, false);
			}
			catch (UserException $ex) {
				$e = Emails::createEmail($email);
				$e->setFailedValidation($ex->getMessage());
				$data = null;
			}
		}

		DB::getInstance()->insert('mailings_recipients', [
			'id_mailing' => $this->id,
			'id_email'   => $e ? $e->id : null,
			'email'      => $email,
			'extra_data' => $data ? json_encode($data) : null,
		]);
	}

	public function listRecipients(): \Generator
	{
		$db = DB::getInstance();
		$sql = sprintf('SELECT email, extra_data AS data, %s AS _name FROM mailings_recipients WHERE id_mailing = %d ORDER BY id;',
			$this->getNameFieldsSQL(),
			$this->id()
		);

		foreach ($db->iterate($sql) as $row) {
			$data = $row->data ? json_decode($row->data) : null;
			yield $row->email => [
				'email' => $row->email,
				'data' => $data,
				'_name' => $row->_name ?? null,
				'pgp_key' => $data->pgp_key ?? null,
			];
		}
	}

	protected function getNameFieldsSQL(string $prefix = ''): string
	{
		$prefix = $prefix ? $prefix . '.' : $prefix;
		$fields = DynamicFields::getNameFields();
		$fields = array_map(fn($a) => sprintf('json_extract(%sextra_data, \'$.%s\')', $prefix, $a), $fields);
		$fields = implode(' || \' \' || ', $fields);
		return $fields;
	}

	public function getRecipientsList(): DynamicList
	{

		$columns = [
			'id' => [
				'select' => 'r.id',
			],
			'id_email' => [
				'select' => 'r.id_email',
			],
			'email' => [
				'label' => 'Adresse',
				'order' => 'r.email COLLATE NOCASE %s',
				'select' => 'r.email',
			],
			'name' => [
				'label' => 'Nom',
				'select' => $this->getNameFieldsSQL('r'),
			],
			'status' => [
				'label' => 'Erreur',
				'select' => sprintf('CASE WHEN o.email_hash IS NOT NULL THEN \'Désinscription de cet envoi\' ELSE (%s) END', Emails::getRejectionStatusClause('e')),
			],
		];

		$tables = 'mailings_recipients AS r
			LEFT JOIN emails e ON e.id = r.id_email
			LEFT JOIN mailings_optouts o ON e.hash = o.email_hash AND o.target_type = :target_type AND o.target_value = :target_value';
		$conditions = 'id_mailing = ' . $this->id;

		$list = new DynamicList($columns, $tables, $conditions);
		$list->setParameter(':target_type', $this->target_type);
		$list->setParameter(':target_value', $this->target_value);
		$list->orderBy('email', false);
		return $list;
	}

	public function countRecipients(): int
	{
		return DB::getInstance()->count('mailings_recipients', 'id_mailing = ?', $this->id);
	}

	public function anonymize(): void
	{
		DB::getInstance()->preparedQuery('UPDATE mailings_recipients SET email = NULL, extra_data = NULL WHERE id_mailing = ?;', $this->id);
	}

	public function deleteRecipient(int $id): void
	{
		DB::getInstance()->delete('mailings_recipients', 'id = ? AND id_mailing = ?', $id, $this->id);
	}

/*
	public function populateFromCSV(string $list): void
	{
		$list = explode("\n", $list);
		$emails = [];

		foreach ($list as $line) {
			$line = trim($line);

			$address = strtok(';')
		}
	}
*/

	public function getFrom(): string
	{
		$config = Config::getInstance();
		return sprintf('"%s" <%s>', $this->sender_name ?? $config->org_name, $this->sender_email ?? $config->org_email);
	}

	/**
	 * @return UserTemplate|string
	 */
	public function getBody()
	{
		return UserTemplate::createFromUserString($this->body ?? '') ?? $this->body;
	}

	public function getPreview(string $address = null): string
	{
		$db = DB::getInstance();

		$where = $address ? 'email = ?' : '1 ORDER BY RANDOM()';
		$sql = sprintf('SELECT extra_data FROM mailings_recipients WHERE %s LIMIT 1;', $where);
		$args = $address ? (array)$address : [];

		$r = $db->firstColumn($sql, ...$args);

		if (!$r) {
			throw new UserException('Cette adresse ne fait pas partie des destinataires: ' . $address);
		}

		$r = json_decode($r, true);

		$body = $this->getBody();

		if ($body instanceof UserTemplate) {
			$body->assignArray($r);

			try {
				$body = $body->fetch();
			}
			catch (\KD2\Brindille_Exception $e) {
				throw new UserException('Erreur de syntaxe dans le corps du message :' . PHP_EOL . $e->getPrevious()->getMessage(), 0, $e);
			}
		}

		$render = Render::FORMAT_MARKDOWN;
		return Render::render($render, null, $body);
	}

	public function getHTMLPreview(string $address = null, bool $append_footer = false): string
	{
		$html = $this->getPreview($address);
		$tpl = new UserTemplate('web/email.html');
		$tpl->assignArray(compact('html'));

		$out = $tpl->fetch();

		if ($append_footer) {
			$out = Emails::appendHTMLOptoutFooter($out, 'javascript:alert(\'--\');');
		}

		return $out;
	}

	public function send(): void
	{
		$this->selfCheck();

		if (!isset($this->body)) {
			throw new UserException('Le corps du message est vide.');
		}

		$sender = null;

		if (isset($this->sender_name, $this->sender_email)) {
			$sender = Emails::getFromHeader($this->sender_name, $this->sender_email);
		}

		Emails::queue(Emails::CONTEXT_BULK,
			$this->listRecipients(),
			$sender,
			$this->subject,
			$this->getBody()
		);

		$this->set('sent', new DateTime);

		$this->save();

		Log::add(Log::SENT, ['entity' => get_class($this), 'id' => $this->id()]);
	}

	public function export(string $format): void
	{
		$rows = [];

		foreach ($this->listRecipients() as $row) {
			$rows[] = [$row['email'] ?? '(Anonymisée)', $row['_name'] ?? ''];
		}

		CSV::export($format, 'Destinataires message collectif', $rows, ['Adresse e-mail', 'Identité']);
	}
}
