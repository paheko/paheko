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

use Paheko\Entities\Users\DynamicField;

use DateTime;
use stdClass;

class Mailing extends Entity
{
	const TABLE = 'mailings';
	const NAME = 'Message collectif';
	const PRIVATE_URL = '!users/mailing/details.php?id=%d';

	protected ?int $id = null;
	protected string $subject;
	protected ?string $body;

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

	public function populate(string $target, ?int $target_id = null): void
	{
		if ($target !== 'all' && empty($target_id)) {
			throw new \InvalidArgumentException('Missing target ID');
		}

		if ($target == 'all') {
			$recipients = Users::iterateEmailsByCategory(null);
		}
		elseif ($target == 'category') {
			$recipients = Users::iterateEmailsByCategory($target_id);
		}
		elseif ($target == 'search') {
			$recipients = Users::iterateEmailsBySearch($target_id);
		}
		elseif ($target == 'service') {
			$recipients = Users::iterateEmailsByActiveService($target_id);
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

		$db->commit();
	}

	public function addRecipient(string $email, ?stdClass $data = null): void
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

		$this->cleanExtraData($data);

		DB::getInstance()->insert('mailings_recipients', [
			'id_mailing' => $this->id,
			'id_email'   => $e ? $e->id : null,
			'email'      => $email,
			'extra_data' => $data ? json_encode($data) : null,
		]);
	}

	protected function cleanExtraData(?stdClass &$data): void
	{
		if (null === $data) {
			return;
		}

		// Clean up users, just in case password/PGP key/etc. are included
		foreach (DynamicField::SYSTEM_FIELDS as $key => $type) {
			unset($data->$key);
		}

		// Just in case the password has another column name
		foreach ($data as $key => $value) {
			if (is_string($value) && substr($value, 0, 2) === '$2') {
				unset($data->$key);
			}
		}
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
		$db = DB::getInstance();
		$out = [];

		foreach ($fields as $field) {
			$field = $db->quote('$.' . $field);
			$out[] = sprintf('json_extract(%sextra_data, %s)', $prefix, $field);
		}

		$out = implode(' || \' \' || ', $out);
		return $out;
	}

	public function getRecipientsList(): DynamicList
	{
		$db = DB::getInstance();
		$columns = [
			'id' => [
				'select' => 'r.id',
			],
			'id_user' => [
				'select' => sprintf('json_extract(r.extra_data, %s)', $db->quote('$.id')),
			],
			'id_email' => [
				'select' => 'r.id_email',
			],
			'user_number' => [
				'label' => 'Numéro de membre',
				'select' => sprintf('json_extract(r.extra_data, %s)', $db->quote('$.' . DynamicFields::getNumberField())),
				'export' => true,
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
				'select' => Emails::getRejectionStatusClause('e'),
			],
			'has_extra_data' => [
				'select' => 'r.extra_data IS NOT NULL',
			],
		];

		$tables = 'mailings_recipients AS r LEFT JOIN emails e ON e.id = r.id_email';
		$conditions = 'id_mailing = ' . $this->id;

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('email', false);
		$list->setTitle('Liste des destinataires');
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

	public function getRecipientExtraData(int $id): ?stdClass
	{
		$value = DB::getInstance()->firstColumn('SELECT extra_data FROM mailings_recipients WHERE id = ?;', $id);
		$value = !$value ? null : json_decode($value, false);

		$this->cleanExtraData($value);
		return $value;
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
		if (!isset($this->body)) {
			return '';
		}

		if (false !== strpos($this->body, '{{')) {
			return UserTemplate::createFromUserString($this->body);
		}
		else {
			return $this->body;
		}
	}

	public function getPreview(int $id = null): string
	{
		$db = DB::getInstance();

		$where = $id ? 'id = ?' : '1 ORDER BY RANDOM()';
		$sql = sprintf('SELECT extra_data FROM mailings_recipients WHERE %s LIMIT 1;', $where);
		$args = $id ? (array)$id : [];

		$r = $db->firstColumn($sql, ...$args);

		if (!$r) {
			throw new UserException('Cette adresse ne fait pas partie des destinataires');
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
}
