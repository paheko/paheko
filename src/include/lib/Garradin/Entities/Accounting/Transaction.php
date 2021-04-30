<?php

namespace Garradin\Entities\Accounting;

use KD2\DB\EntityManager;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Config;
use Garradin\Utils;
use Garradin\UserException;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

use Garradin\Accounting\Accounts;
use Garradin\ValidationException;

class Transaction extends Entity
{
	const TABLE = 'acc_transactions';

	const TYPE_ADVANCED = 0;
	const TYPE_REVENUE = 1;
	const TYPE_EXPENSE = 2;
	const TYPE_TRANSFER = 3;
	const TYPE_DEBT = 4;
	const TYPE_CREDIT = 5;

	const STATUS_WAITING = 1;
	const STATUS_PAID = 2;
	const STATUS_DEPOSIT = 4;
	const STATUS_ERROR = 8;

	const STATUS_NAMES = [
		1 => 'En attente de règlement',
		2 => 'Réglé',
		4 => 'Déposé en banque',
	];

	const TYPES_NAMES = [
		'Avancé',
		'Recette',
		'Dépense',
		'Virement',
		'Dette',
		'Créance',
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

	/**
	 * @var Transaction
	 */
	protected $_related;

	static public function getTypeFromAccountType(int $account_type)
	{
		switch ($account_type) {
			case Account::TYPE_REVENUE:
				return self::TYPE_REVENUE;
			case Account::TYPE_EXPENSE:
				return self::TYPE_EXPENSE;
			case Account::TYPE_THIRD_PARTY:
				return self::TYPE_DEBT;
			case Account::TYPE_BANK:
			case Account::TYPE_CASH:
			case Account::TYPE_OUTSTANDING:
				return self::TYPE_TRANSFER;
			default:
				return self::TYPE_ADVANCED;
		}
	}

	/**
	 * @param  bool $restrict_year Set to TRUE to only return lines linked to the correct chart, or FALSE (deprecated/legacy) to return all lines even if they are linked to accounts in the wrong chart!
	 */
	public function getLinesWithAccounts(bool $restrict_year = true)
	{
		$restrict = $restrict_year ? 'AND a.id_chart = y.id_chart' : '';

		$sql = sprintf('SELECT
			l.*, a.label AS account_name, a.code AS account_code,
			b.label AS analytical_name
			FROM acc_transactions_lines l
			INNER JOIN acc_accounts a ON a.id = l.id_account %s
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			INNER JOIN acc_years y ON y.id = t.id_year
			LEFT JOIN acc_accounts b ON b.id = l.id_analytical
			WHERE l.id_transaction = ? ORDER BY l.id;', $restrict);

		$em = EntityManager::getInstance(Line::class);
		return $em->DB()->get($sql, $this->id);
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

	public function removeLine(Line $remove)
	{
		$new = [];

		foreach ($this->getLines() as $line) {
			if ($line->id === $remove->id) {
				$this->_old_lines[] = $remove;
			}
			else {
				$new[] = $line;
			}
		}

		$this->_lines = $new;
	}

	public function resetLines()
	{
		$this->_old_lines = $this->getLines();
		$this->_lines = [];
	}

	public function getLine(int $id)
	{
		foreach ($this->getLines() as $line) {
			if ($line->id === $id) {
				return $line;
			}
		}

		return null;
	}

	public function getFirstLine()
	{
		$lines = $this->getLines();

		if (!count($lines)) {
			return null;
		}

		return reset($lines);
	}

	public function getLinesCreditSum()
	{
		$sum = 0;

		foreach ($this->getLines() as $line) {
			$sum += $line->credit;
		}

		return $sum;
	}

	public function getLinesDebitSum()
	{
		$sum = 0;

		foreach ($this->getLines() as $line) {
			$sum += $line->debit;
		}

		return $sum;
	}

	public function getAnalyticalId(): ?int
	{
		$lines = $this->getLines();

		if (!count($lines)) {
			return null;
		}

		return current($lines)->id_analytical;
	}

	public function getTypesAccounts()
	{
		if ($this->type == self::TYPE_ADVANCED) {
			return [];
		}

		$debit = null;
		$credit = null;

		$lines = $this->getLinesWithAccounts();

		foreach ($lines as $line) {
			$account = [$line->id_account => sprintf('%s — %s', $line->account_code, $line->account_name)];

			if ($line->debit) {
				$debit = $account;
			}
			else {
				$credit = $account;
			}
		}

		$type = $this->getTypesDetails()[$this->type];

		return [
			$type->accounts[0]->position == 'credit' ? $credit : $debit,
			$type->accounts[1]->position == 'credit' ? $credit : $debit,
		];
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

	public function addLine(Line $line)
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
		if ($this->validated && empty($this->_modified['validated'])) {
			throw new ValidationException('Il n\'est pas possible de modifier une écriture qui a été validée');
		}

		$db = DB::getInstance();

		if ($db->test(Year::TABLE, 'id = ? AND closed = 1', $this->id_year)) {
			throw new ValidationException('Il n\'est pas possible de créer ou modifier une écriture dans un exercice clôturé');
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
		if ((self::TYPE_DEBT == $this->type || self::TYPE_CREDIT == $this->type) && $this->_related) {
			$this->_related->markPaid();
			$this->_related->save();
		}

		return true;
	}

	public function removeStatus(int $property) {
		$this->set('status', $this->status & ~$property);
	}

	public function addStatus(int $property) {
		$this->set('status', $this->status | $property);
	}

	public function markPaid() {
		$this->removeStatus(self::STATUS_WAITING);
		$this->addStatus(self::STATUS_PAID);
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

		Files::delete($this->getAttachementsDirectory());

		return parent::delete();
	}

	public function selfCheck(): void
	{
		parent::selfCheck();
		$db = DB::getInstance();

		$this->assert(null !== $this->id_year, 'Aucun exercice spécifié.');
		$this->assert(array_key_exists($this->type, self::TYPES_NAMES), 'Type d\'écriture inconnu : ' . $this->type);
		$this->assert(null === $this->id_creator || $db->test('membres', 'id = ?', $this->id_creator), 'Le membre créateur de l\'écriture n\'existe pas ou plus');

		$is_in_year = $db->test(Year::TABLE, 'id = ? AND start_date <= ? AND end_date >= ?', $this->id_year, $this->date->format('Y-m-d'), $this->date->format('Y-m-d'));

		$this->assert($is_in_year, 'La date ne correspond pas à l\'exercice sélectionné : ' . $this->date->format('d/m/Y'));

		$total = 0;

		$lines = $this->getLines();

		foreach ($lines as $line) {
			$total += $line->credit;
			$total -= $line->debit;
		}

		$this->assert(0 === $total, sprintf('Écriture non équilibrée : déséquilibre (%s) entre débits et crédits', Utils::money_format($total)));
	}

	public function importFromDepositForm(?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (empty($source['amount'])) {
			throw new UserException('Montant non précisé');
		}

		$this->type = self::TYPE_ADVANCED;
		$amount = $source['amount'];

		$key = 'account_transfer';
		$account = isset($source[$key]) && @count($source[$key]) ? key($source[$key]) : null;

		$line = new Line;
		$line->importForm([
			'debit'      => $amount,
			'credit'     => 0,
			'id_account' => $account,
		]);

		$this->addLine($line);

		$this->importForm($source);
	}

	public function importFromNewForm(?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (!isset($source['type'])) {
			throw new ValidationException('Type d\'écriture inconnu');
		}

		$type = $source['type'];

		$this->importForm($source);

		if (self::TYPE_ADVANCED == $type) {
			if (!isset($source['lines']) || !is_array($source['lines'])) {
				throw new ValidationException('Aucune ligne dans la saisie');
			}

			$lines = Utils::array_transpose($source['lines']);

			foreach ($lines as $i => $line) {
				$line['id_account'] = @count($line['account']) ? key($line['account']) : null;

				if (!$line['id_account']) {
					throw new ValidationException('Numéro de compte invalide sur la ligne ' . ((int) $i+1));
				}

				$line = (new Line)->import($line);
				$this->addLine($line);
			}
		}
		else {
			$details = self::getTypesDetails();

			if (!array_key_exists($type, $details)) {
				throw new ValidationException('Type d\'écriture inconnu');
			}

			if (empty($this->_related) && ($type == self::TYPE_DEBT || $type == self::TYPE_CREDIT)) {
				$this->addStatus(self::STATUS_WAITING);
			}
			else {
				$this->removeStatus(self::STATUS_WAITING);
			}

			if (empty($source['amount'])) {
				throw new UserException('Montant non précisé');
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
				$this->addLine($line);
			}
		}

		// Remove error status when changed
		$this->removeStatus(self::STATUS_ERROR);
	}

	public function importFromEditForm(?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}


		$this->resetLines();
		$this->importFromNewForm($source);
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
		$this->type = self::TYPE_ADVANCED;

		try {
			$lines = Utils::array_transpose($source['lines']);
		}
		catch (\LogicException $e) {
			throw new ValidationException('Aucun compte sélectionné pour certaines lignes.');
		}

		$debit = $credit = 0;

		foreach ($lines as $k => $line) {
			$line['id_account'] = @count($line['account']) ? key($line['account']) : null;

			try {
				$line = (new Line)->importForm($line);
				$this->addLine($line);
			}
			catch (ValidationException $e) {
				throw new ValidationException(sprintf('Ligne %d : %s', $k+1, $e->getMessage()), 0, $e);
			}

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

			$this->addLine($line);
		}
	}

	public function year()
	{
		return EntityManager::findOneById(Year::class, $this->id_year);
	}

	public function listFiles()
	{
		return Files::list($this->getAttachementsDirectory());
	}

	public function getAttachementsDirectory(): string
	{
		return File::CONTEXT_TRANSACTION . '/' . $this->id();
	}

	public function linkToUser(int $user_id, ?int $service_id = null)
	{
		$db = EntityManager::getInstance(self::class)->DB();

		return $db->preparedQuery('INSERT OR IGNORE INTO acc_transactions_users (id_transaction, id_user, id_service_user) VALUES (?, ?, ?);',
			$this->id(), $user_id, $service_id);
	}

	public function updateLinkedUsers(array $users)
	{
		$db = EntityManager::getInstance(self::class)->DB();

		$db->begin();

		$sql = sprintf('DELETE FROM acc_transactions_users WHERE id_transaction = ? AND id_service_user IS NULL AND %s;', $db->where('id_user', 'NOT IN', $users));
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
		$sql = sprintf('SELECT m.id, m.%s AS identity, l.id_service_user FROM membres m INNER JOIN acc_transactions_users l ON l.id_user = m.id WHERE l.id_transaction = ?;', $identity_column);
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
			self::TYPE_REVENUE => [
				'accounts' => [
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
				'label' => self::TYPES_NAMES[self::TYPE_REVENUE],
			],
			self::TYPE_EXPENSE => [
				'accounts' => [
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
				'label' => self::TYPES_NAMES[self::TYPE_EXPENSE],
				'help' => null,
			],
			self::TYPE_TRANSFER => [
				'accounts' => [
					[
						'label' => 'De',
						'targets' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
						'position' => 'credit',
					],
					[
						'label' => 'Vers',
						'targets' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
						'position' => 'debit',
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_TRANSFER],
				'help' => 'Dépôt en banque, virement interne, etc.',
			],
			self::TYPE_DEBT => [
				'accounts' => [
					[
						'label' => 'Compte de tiers',
						'targets' => [Account::TYPE_THIRD_PARTY],
						'position' => 'credit',
					],
					[
						'label' => 'Type de dette (dépense)',
						'targets' => [Account::TYPE_EXPENSE],
						'position' => 'debit',
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_DEBT],
				'help' => 'Quand l\'association doit de l\'argent à un membre ou un fournisseur',
			],
			self::TYPE_CREDIT => [
				'accounts' => [
					[
						'label' => 'Compte de tiers',
						'targets' => [Account::TYPE_THIRD_PARTY],
						'position' => 'debit',
					],
					[
						'label' => 'Type de créance (recette)',
						'targets' => [Account::TYPE_REVENUE],
						'position' => 'credit',
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_CREDIT],
				'help' => 'Quand un membre ou un fournisseur doit de l\'argent à l\'association',
			],
			self::TYPE_ADVANCED => [
				'accounts' => [],
				'label' => self::TYPES_NAMES[self::TYPE_ADVANCED],
				'help' => 'Choisir les comptes du plan comptable, ventiler une écriture sur plusieurs comptes, etc.',
			],
		];

		foreach ($details as $key => &$type) {
			$type = (object) $type;
			$type->id = $key;
			foreach ($type->accounts as &$account) {
				$account = (object) $account;
				$account->targets_string = implode(':', $account->targets);
			}
		}

		return $details;
	}

	public function payOffFrom(int $id): \stdClass
	{
		$this->_related = EntityManager::findOneById(self::class, $id);

		if (!$this->_related) {
			throw new \LogicException('Écriture d\'origine invalide');
		}

		$this->id_related = $this->_related->id();
		$this->label = ($this->_related->type == Transaction::TYPE_DEBT ? 'Règlement de dette : ' : 'Règlement de créance : ') . $this->_related->label;
		$this->type = $this->_related->type;

		$out = (object) [
			'id' => $this->_related->id,
			'sum' => $this->_related->sum(),
			'id_account' => null,
			'form_account_name' => sprintf('account_%d_%d', $this->type, 1),
			'form_target_name' => sprintf('account_%d_%d', $this->type, 0),
		];

		foreach ($this->_related->getLines() as $line) {
			if (($this->_related->type == self::TYPE_DEBT && $line->debit)
				|| ($this->_related->type == self::TYPE_CREDIT && $line->credit)) {
				// Skip the type of debt/credit, just keep the thirdparty account
				continue;
			}

			$out->id_account = $line->id_account;
			break;
		}

		return $out;
	}

	public function getTypeName(): string
	{
		return self::TYPES_NAMES[$this->type];
	}

	public function asDetailsArray(): array
	{
		$lines = [];

		foreach ($this->getLines() as $line) {
			$lines[] = $line->asDetailsArray();
		}

		return [
			'Libellé'         => $this->label,
			'Date'            => $this->date,
			'Pièce comptable' => $this->reference,
			'Remarques'       => $this->notes,
			'Total crédit'    => Utils::money_format($this->getLinesCreditSum()),
			'Total débit'     => Utils::money_format($this->getLinesDebitSum()),
			'Lignes'          => $lines,
		];
	}
}