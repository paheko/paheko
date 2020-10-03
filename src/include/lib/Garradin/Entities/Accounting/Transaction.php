<?php

namespace Garradin\Entities\Accounting;

use KD2\DB\EntityManager;
use Garradin\Entity;
use Garradin\Fichiers;
use Garradin\Accounting\Accounts;
use Garradin\ValidationException;
use Garradin\DB;
use Garradin\Config;
use Garradin\Utils;

class Transaction extends Entity
{
	const TABLE = 'acc_transactions';

	protected $id;
	protected $label;
	protected $notes;
	protected $reference;

	protected $date;

	protected $validated = 0;

	protected $hash;
	protected $prev_hash;

	protected $id_year;
	protected $id_creator;

	protected $_types = [
		'id'        => 'int',
		'label'     => 'string',
		'notes'     => '?string',
		'reference' => '?string',
		'date'      => 'date',
		'validated' => 'bool',
		'hash'      => '?string',
		'prev_hash' => '?string',
		'id_year'   => 'int',
		'id_creator' => '?int',
	];

	protected $_form_rules = [
		'label'     => 'required|string|max:200',
		'notes'     => 'string|max:20000',
		'reference' => 'string|max:200',
		'date'      => 'required|date_format:d/m/Y',
	];

	protected $_lines;
	protected $_old_lines = [];

	public function getLinesWithAccounts()
	{
		$em = EntityManager::getInstance(Line::class);
		return $em->DB()->get('SELECT a.*, b.label AS account_name, b.code AS account_code FROM ' . Line::TABLE  .' a INNER JOIN ' . Account::TABLE . ' b ON b.id = a.id_account WHERE a.id_transaction = ? ORDER BY a.id;', $this->id);
	}

	public function getLines($with_accounts = false)
	{
		if (null === $this->_lines && $this->exists()) {
			$em = EntityManager::getInstance(Line::class);
			$this->_lines = $em->all('SELECT * FROM @TABLE WHERE id_transaction = ? ORDER BY id;', $this->id);
		}
		elseif (null === $this->_lines) {
			$this->_lines = [];
		}

		return $this->_lines;
	}

/*
	public function getHash()
	{
		if (!$this->id_year) {
			throw new \LogicException('Il n\'est pas possible de hasher un mouvement qui n\'est pas associé à un exercice');
		}

		static $keep_keys = [
			'label',
			'notes',
			'reference',
			'date',
			'validated',
			'prev_hash',
		];

		$hash = hash_init('sha256');
		$values = $this->asArray();
		$values = array_intersect_key($values, $keep_keys);

		hash_update($hash, implode(',', array_keys($values)));
		hash_update($hash, implode(',', $values));

		foreach ($this->getLines() as $line) {
			hash_update($hash, implode(',', [$line->compte, $line->debit, $line->credit]));
		}

		return hash_final($hash, false);
	}

	public function checkHash()
	{
		return hash_equals($this->getHash(), $this->hash);
	}
*/

	public function add(Line $line)
	{
		$this->_lines[] = $line;
	}

	public function save(): bool
	{
		if ($this->validated && !isset($this->_modified['validated'])) {
			throw new ValidationException('Il n\'est pas possible de modifier une écriture qui a été validé');
		}

		$db = DB::getInstance();

		if ($db->test(Year::TABLE, 'id = ? AND closed = 1', $this->id_year)) {
			throw new ValidationException('Il n\'est pas possible de modifier une écriture qui fait partie d\'un exercice clôturé');
		}

		if (!parent::save()) {
			return false;
		}

		foreach ($this->_lines as &$line)
		{
			$line->id_transaction = $this->id();
			$line->save();
		}

		foreach ($this->_old_lines as $line)
		{
			$line->delete();
		}

		return true;
	}

	public function delete(): bool
	{
		if ($this->validated) {
			throw new ValidationException('Il n\'est pas possible de supprimer une écriture qui a été validée');
		}

		$db = DB::getInstance();

		if ($db->test(Year::TABLE, 'id = ? AND closed = 1', $this->id_year)) {
			throw new ValidationException('Il n\'est pas possible de supprimer une écriture qui fait partie d\'un exercice clôturé');
		}

		Fichiers::deleteLinkedFiles(Fichiers::LIEN_COMPTA, $this->id());

		return parent::delete();
	}

	public function selfCheck(): void
	{
		parent::selfCheck();

		$db = DB::getInstance();
		$config = Config::getInstance();

		// ID d'exercice obligatoire
		if (null === $this->id_year) {
			throw new \LogicException('Aucun exercice spécifié.');
		}

		if (!$db->test(Year::TABLE, 'id = ? AND start_date <= ? AND end_date >= ?;', $this->id_year, $this->date, $this->date))
		{
			throw new ValidationException('La date ne correspond pas à l\'exercice sélectionné.');
		}

		$total = 0;

		$lines = $this->getLines();

		foreach ($lines as $line) {
			$total += $line->credit;
			$total -= $line->debit;
		}

		if (0 !== $total) {
			throw new ValidationException('Écriture non équilibrée : déséquilibre entre débits et crédits');
		}
	}

	public function importFromNewForm(int $chart_id, ?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (empty($source['type'])) {
			throw new ValidationException('Type d\'écriture inconnu');
		}

		$type = $source['type'];

		$this->importForm();

		if ($type !== 'advanced') {
			$from = @count($source[$type . '_from']) ? key($source[$type . '_from']) : null;
			$to = @count($source[$type . '_to']) ? key($source[$type . '_to']) : null;
			$amount = $source['amount'];

			$line = new Line;
			$line->importForm([
				'reference'     => $source['payment_reference'],
				'credit'        => '0',
				'debit'         => $amount,
				'id_account'    => $from,
				'id_analytical' => !empty($source['id_analytical']) ? $source['id_analytical'] : null,
			]);
			$this->add($line);

			$line = new Line;
			$line->importForm([
				'reference'     => $source['payment_reference'],
				'credit'        => $amount,
				'debit'         => '0',
				'id_account'    => $to,
				'id_analytical' => !empty($source['id_analytical']) ? $source['id_analytical'] : null,
			]);
			$this->add($line);
		}
		else {
			$lines = Utils::array_transpose($source['lines']);

			foreach ($lines as $i => $line) {
				$line['id_account'] = @count($line['account']) ? key($line['account']) : null;

				if (!$line['id_account']) {
					throw new ValidationException('Numéro de compte invalide sur la ligne ' . ($i+1));
				}

				$line = (new Line)->import($line);
				$this->add($line);
			}
		}
	}

	public function importFromEditForm(?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		$this->importForm();

		$this->_old_lines = $this->getLines();
		$this->_lines = [];

		$lines = Utils::array_transpose($source['lines']);

		foreach ($lines as $i => $line) {
			$line['id_account'] = @count($line['account']) ? key($line['account']) : null;

			if (!$line['id_account']) {
				throw new ValidationException('Numéro de compte invalide sur la ligne ' . ($i+1));
			}

			$line = (new Line)->importForm($line);
			$this->add($line);
		}
	}

	public function importFromBalanceForm(Year $year, ?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (!isset($source['lines']) || !is_array($source['lines'])) {
			throw new ValidationException('Aucun contenu trouvé dans le formulaire.');
		}

		$this->label = 'Balance d\'ouverture';
		$this->date = $year->start_date;
		$this->id_year = $year->id();

		$lines = Utils::array_transpose($source['lines']);
		$debit = $credit = 0;

		foreach ($lines as $line) {
			$line['id_account'] = @count($line['account']) ? key($line['account']) : null;
			$line = (new Line)->importForm($line);
			$this->add($line);

			$debit += $line->debit;
			$credit += $line->credit;
		}

		if ($debit != $credit) {
			// Add final balance line
			$line = new Line;

			if ($debit > $credit) {
				$line->debit = $debit - $credit;
			}
			else {
				$line->credit = $credit - $debit;
			}

			$open_account = EntityManager::findOne(Account::class, 'SELECT * FROM @TABLE WHERE id_chart = ? AND type = ? LIMIT 1;', $year->id_chart, Account::TYPE_OPENING);

			if (!$open_account) {
				throw new ValidationException('Aucun compte favori de bilan d\'ouverture n\'existe dans le plan comptable');
			}

			$line->id_account = $open_account->id();

			$this->add($line);
		}
	}

	public function year()
	{
		return EntityManager::findOneById(Year::class, $this->id_year);
	}

	public function listFiles()
	{
		return Fichiers::listLinkedFiles(Fichiers::LIEN_COMPTA, $this->id());
	}

	public function updateLinkedUsers(array $users)
	{
		$db = EntityManager::getInstance(self::class)->DB();

		$db->begin();

		$sql = sprintf('DELETE FROM acc_transactions_users WHERE id_transaction = ? AND %s;', $db->where('id_user', 'NOT IN', $users));
		$db->preparedQuery($sql, $this->id());

		foreach ($users as $id) {
			$db->preparedQuery('INSERT OR IGNORE INTO acc_transactions_users (id_transaction, id_user) VALUES (?, ?);', $this->id(), $id);
		}

		$db->commit();
	}

	public function listLinkedUsers()
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$identity_column = Config::getInstance()->get('champ_identite');
		$sql = sprintf('SELECT m.id, m.%s AS identity FROM membres m INNER JOIN acc_transactions_users l ON l.id_user = m.id WHERE l.id_transaction = ?;', $identity_column);
		return $db->get($sql, $this->id());
	}

	public function listLinkedUsersAssoc()
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$identity_column = Config::getInstance()->get('champ_identite');
		$sql = sprintf('SELECT m.id, m.%s AS identity FROM membres m INNER JOIN acc_transactions_users l ON l.id_user = m.id WHERE l.id_transaction = ?;', $identity_column);
		return $db->getAssoc($sql, $this->id());
	}
}