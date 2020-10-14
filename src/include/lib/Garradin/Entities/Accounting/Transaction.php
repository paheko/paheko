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

	const TYPE_ADVANCED = 0;
	const TYPE_REVENUE = 1;
	const TYPE_EXPENSE = 2;
	const TYPE_TRANSFER = 3;
	const TYPE_DEBT = 4;
	const TYPE_CREDIT = 5;
	const TYPE_PAYOFF = 6;

	const STATUS_WAITING = 1;
	const STATUS_PAID = 2;

	const TYPES_NAMES = [
		'Avancé',
		'Recette',
		'Dépense',
		'Virement',
		'Dette',
		'Créance',
		'Règlement',
	];

	protected $id;
	protected $type;
	protected $status = 0;
	protected $label;
	protected $notes;
	protected $reference;

	protected $date;

	protected $validated = 0;

	protected $hash;
	protected $prev_hash;

	protected $id_year;
	protected $id_creator;
	protected $id_related;

	protected $_types = [
		'id'        => 'int',
		'type'      => 'int',
		'status'    => 'int',
		'label'     => 'string',
		'notes'     => '?string',
		'reference' => '?string',
		'date'      => 'date',
		'validated' => 'bool',
		'hash'      => '?string',
		'prev_hash' => '?string',
		'id_year'   => 'int',
		'id_creator' => '?int',
		'id_related' => '?int',
	];

	protected $_form_rules = [
		'label'     => 'required|string|max:200',
		'notes'     => 'string|max:20000',
		'reference' => 'string|max:200',
		'date'      => 'required|date_format:d/m/Y',
	];

	protected $_lines;
	protected $_old_lines = [];

	protected $_related;

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

	public function sum(): int
	{
		$sum = 0;

		foreach ($this->getLines() as $line) {
			$sum += $line->credit;
			// Because credit == debit, we only use credit
		}

		return $sum;
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

		foreach ($this->getLines() as $line)
		{
			$line->id_transaction = $this->id();
			$line->save();
		}

		foreach ($this->_old_lines as $line)
		{
			$line->delete();
		}

		// Remove flag
		if (self::TYPE_PAYOFF == $this->type && $this->_related) {
			$status = $this->_related->status;
			$status &= ~self::STATUS_WAITING;
			$status |= self::STATUS_PAID;
			$this->_related->set('status', $status );
			$this->_related->save();
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

		if (!$db->test(Year::TABLE, 'id = ? AND start_date <= ? AND end_date >= ?;', $this->id_year, $this->date->format('Y-m-d'), $this->date->format('Y-m-d')))
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

		if (!array_key_exists($this->type, self::TYPES_NAMES)) {
			throw new ValidationException('Type d\'écriture inconnu');
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

		if (self::TYPE_PAYOFF == $type) {
			$amount = $source['amount'];

			$key = 'account_payoff';
			$account = isset($source[$key]) && @count($source[$key]) ? key($source[$key]) : null;

			if (!count($this->getLines())) {
				throw new \LogicException('Invalid operation: payoff must already have one line');
			}

			$line = current($this->_lines);
			$debit = $line->debit ? 0 : $amount;
			$credit = $line->credit ? 0 : $amount;

			$line2 = new Line;
			$line2->importForm([
				'reference'     => $source['payment_reference'],
				'credit'        => $credit,
				'debit'         => $debit,
				'id_account'    => $account,
				'id_analytical' => !empty($source['id_analytical']) ? $source['id_analytical'] : null,
			]);
			$this->add($line2);
		}
		elseif (self::TYPE_ADVANCED == $type) {
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
		else {
			$details = self::getTypesDetails();

			if (!array_key_exists($type, $details)) {
				throw new ValidationException('Type d\'écriture inconnu');
			}

			if ($type == self::TYPE_DEBT || $type == self::TYPE_CREDIT) {
				$this->status = self::STATUS_WAITING;
			}

			$amount = $source['amount'];

			// Fill lines using a pre-defined setup obtained from getTypesDetails
			foreach ($details[$type]->accounts as $k => $account) {
				$credit = $account->position == 'credit' ? $amount : 0;
				$debit = $account->position == 'debit' ? $amount : 0;
				$key = sprintf('account_%d_%d', $type, $k);
				$account = isset($source[$key]) && @count($source[$key]) ? key($source[$key]) : null;

				$line = new Line;
				$line->importForm([
					'reference'     => $source['payment_reference'],
					'credit'        => $credit,
					'debit'         => $debit,
					'id_account'    => $account,
					'id_analytical' => !empty($source['id_analytical']) ? $source['id_analytical'] : null,
				]);
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

	static public function getTypesDetails()
	{
		$details = [
			[
				[
					'label' => 'Type de recette',
					'targets' => [Account::TYPE_REVENUE],
					'position' => 'credit',
				],
				[
					'label' => 'Compte d\'encaissement',
					'targets' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
					'position' => 'debit',
				],
			],
			[
				[
					'label' => 'Type de dépense',
					'targets' => [Account::TYPE_EXPENSE],
					'position' => 'debit',
				],
				[
					'label' => 'Compte de décaissement',
					'targets' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
					'position' => 'credit',
				],
			],
			[
				[
					'label' => 'De',
					'targets' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
					'position' => 'debit',
				],
				[
					'label' => 'Vers',
					'targets' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
					'position' => 'credit',
				],
			],
			[
				[
					'label' => 'Compte de tiers',
					'targets' => [Account::TYPE_THIRD_PARTY],
					'position' => 'debit',
				],
				[
					'label' => 'Type de dette (dépense)',
					'targets' => [Account::TYPE_EXPENSE],
					'position' => 'credit',
				],
			],
			[
				[
					'label' => 'Compte de tiers',
					'targets' => [Account::TYPE_THIRD_PARTY],
					'position' => 'credit',
				],
				[
					'label' => 'Type de créance (recette)',
					'targets' => [Account::TYPE_REVENUE],
					'position' => 'debit',
				],
			],
		];

		$out = [];

		foreach ($details as $k => $accounts) {
			$d = (object) [
				'id' => $k+1,
				'label' => self::TYPES_NAMES[$k+1],
				'accounts' => [],
			];

			foreach ($accounts as $account) {
				$account['targets'] = implode(':', $account['targets']);
				$d->accounts[] = (object) $account;
			}

			$out[$d->id] = $d;
		}

		return $out;
	}

	public function payOffFrom(int $id): self
	{
		$this->_related = EntityManager::findOneById(self::class, $id);

		if (!$this->_related) {
			throw new \LogicException('Écriture d\'origine invalide');
		}

		$this->id_related = $this->_related->id();
		$this->label = ($this->_related->type == Transaction::TYPE_DEBT ? 'Règlement de dette : ' : 'Règlement de créance : ') . $this->_related->label;
		$this->type = Transaction::TYPE_PAYOFF;

		foreach ($this->_related->getLines() as $line) {
			if (($this->_related->type == self::TYPE_DEBT && $line->credit)
				|| ($this->_related->type == self::TYPE_CREDIT && $line->debit)) {
				// Skip the type of debt/credit, just keep the thirdparty account
				continue;
			}

			// Invert debit/credit
			$line2 = clone $line;
			$line2->debit = $line->debit ? 0 : $line->credit;
			$line2->credit = $line->credit ? 0 : $line->debit;
			$this->add($line2);
		}

		return $this->_related;
	}
}