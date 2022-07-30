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
	protected $type = null;
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

	protected $_lines;
	protected $_old_lines = [];

	protected $_accounts = [];

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

	public function getLinesWithAccounts(): array
	{
		$db = EntityManager::getInstance(Line::class)->DB();

		// Merge data from accounts with lines
		$accounts = [];
		$lines_with_accounts = [];

		foreach ($this->getLines() as $line) {
			if (!array_key_exists($line->id_account, $this->_accounts)) {
				$accounts[] = $line->id_account;
			}

			if ($line->id_analytical && !array_key_exists($line->id_analytical, $this->_accounts)) {
				$accounts[] = $line->id_analytical;
			}
		}

		// Remove NULL accounts
		$accounts = array_filter($accounts);

		if (count($accounts)) {
			$sql = sprintf('SELECT id, label, code, position FROM acc_accounts WHERE %s;', $db->where('id', 'IN', $accounts));
			$this->_accounts = $this->_accounts + $db->getGrouped($sql);
		}

		foreach ($this->getLines() as &$line) {
			$l = (object) $line->asArray();
			$l->account_code = $this->_accounts[$line->id_account]->code ?? null;
			$l->account_label = $this->_accounts[$line->id_account]->label ?? null;
			$l->account_position = $this->_accounts[$line->id_account]->position ?? null;
			$l->analytical_name = $this->_accounts[$line->id_analytical]->label ?? null;
			$l->account_selector = [$line->id_account => sprintf('%s — %s', $l->account_code, $l->account_label)];
			$l->line =& $line;

			$lines_with_accounts[] = $l;
		}

		unset($line);

		return $lines_with_accounts;
	}

	public function getLines(): array
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

	public function countLines(): int
	{
		return count($this->getLines());
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

	static public function getFormLines(?array $source = null): array
	{
		if (null === $source) {
			$source = $_POST['lines'] ?? [];
		}

		if (empty($source) || !is_array($source)) {
			return [];
		}

		$lines = Utils::array_transpose($source);

		foreach ($lines as &$line) {
			if (isset($line['credit'])) {
				$line['credit'] = Utils::moneyToInteger($line['credit']);
			}
			if (isset($line['debit'])) {
				$line['debit'] = Utils::moneyToInteger($line['debit']);
			}
		}

		unset($line);

		return $lines;
	}

	public function hasReconciledLines(): bool
	{
		foreach ($this->getLines() as $line) {
			if (!empty($line->reconciled)) {
				return true;
			}
		}

		return false;
	}

	public function getAnalyticalId(): ?int
	{
		$lines = $this->getLines();

		if (!count($lines)) {
			return null;
		}

		return current($lines)->id_analytical;
	}

	public function related(): ?Transaction
	{
		return $this->_related;
	}

	/**
	 * Creates a new Transaction entity (not saved) from an existing one,
	 * trying to adapt to a different chart if possible
	 * @param  int    $id
	 * @param  Year   $year Target year
	 * @return Transaction
	 */
	public function duplicate(Year $year): Transaction
	{
		$new = new Transaction;

		$copy = ['type', 'status', 'label', 'notes', 'reference'];

		foreach ($copy as $field) {
			$new->$field = $this->$field;
		}

		$copy = ['credit', 'debit', 'id_account', 'label', 'reference', 'id_analytical'];
		$lines = DB::getInstance()->get('SELECT
				l.credit, l.debit, l.label, l.reference, b.id AS id_account, c.id AS id_analytical
			FROM acc_transactions_lines l
			INNER JOIN acc_accounts a ON a.id = l.id_account
			LEFT JOIN acc_accounts b ON b.code = a.code AND b.id_chart = ?
			LEFT JOIN acc_accounts c ON c.id = l.id_analytical AND c.id_chart = ?
			WHERE l.id_transaction = ?;',
			$year->chart()->id,
			$year->chart()->id,
			$this->id()
		);

		foreach ($lines as $l) {
			$line = new Line;

			foreach ($copy as $field) {
				// Do not copy id_account when it is null, as it will trigger an error (invalid entity)
				if ($field == 'id_account' && !isset($l->$field)) {
					continue;
				}

				$line->$field = $l->$field;
			}

			$new->addLine($line);
		}

		// Only set date if valid
		if ($this->date >= $year->start_date && $this->date <= $year->end_date) {
			$new->date = clone $this->date;
		}

		$new->status = 0;

		return $new;
	}

	public function payment_reference(): ?string
	{
		$line = current($this->getLines());

		if (!$line) {
			return null;
		}

		return $line->reference;
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

	public function save(bool $selfcheck = true): bool
	{
		if ($this->type == self::TYPE_DEBT || $this->type == self::TYPE_CREDIT) {
			// Debts and credits add a waiting status
			if (!$this->exists()) {
				$this->addStatus(self::STATUS_WAITING);
			}
		}

		if ($this->validated && !(isset($this->_modified['validated']) && $this->_modified['validated'] === 0)) {
			throw new ValidationException('Il n\'est pas possible de modifier une écriture qui a été validée');
		}

		$db = DB::getInstance();

		if ($db->test(Year::TABLE, 'id = ? AND closed = 1', $this->id_year)) {
			throw new ValidationException('Il n\'est pas possible de créer ou modifier une écriture dans un exercice clôturé');
		}

		$this->selfCheck();

		$lines = $this->getLinesWithAccounts();

		// Self check lines before saving Transaction
		foreach ($lines as $i => $l) {
			$line = $l->line;
			$line->id_transaction = -1; // Get around validation of id_transaction being not null

			if ($this->type == self::TYPE_EXPENSE && $l->account_position == Account::REVENUE) {
				throw new ValidationException('Il n\'est pas possible d\'attribuer un compte de produit à une dépense');
			}

			if ($this->type == self::TYPE_REVENUE && $l->account_position == Account::EXPENSE) {
				throw new ValidationException('Il n\'est pas possible d\'attribuer un compte de dépense à une recette');
			}

			try {
				$line->selfCheck();
			}
			catch (ValidationException $e) {
				// Add line number to message
				throw new ValidationException(sprintf('Ligne %d : %s', $i+1, $e->getMessage()), 0, $e);
			}
		}

		if ($this->exists() && $this->status & self::STATUS_ERROR) {
			// Remove error status when changed
			$this->removeStatus(self::STATUS_ERROR);
		}

		$db->begin();

		if (!parent::save()) {
			return false;
		}

		foreach ($lines as $line) {
			$line = $line->line; // Fetch real object
			$line->id_transaction = $this->id();
			$line->save(false);
		}

		foreach ($this->_old_lines as $line) {
			if ($line->exists()) {
				$line->delete();
			}
		}

		$db->commit();

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
		$db = DB::getInstance();

		$this->assert(!empty($this->id_year), 'L\'ID de l\'exercice doit être renseigné.');

		$this->assert(trim((string)$this->label) !== '', 'Le champ libellé ne peut rester vide.');
		$this->assert(strlen($this->label) <= 200, 'Le champ libellé ne peut faire plus de 200 caractères.');
		$this->assert(!isset($this->reference) || strlen($this->reference) <= 200, 'Le champ numéro de pièce comptable ne peut faire plus de 200 caractères.');
		$this->assert(!isset($this->notes) || strlen($this->notes) <= 2000, 'Le champ remarques ne peut faire plus de 2000 caractères.');
		$this->assert(!empty($this->date), 'Le champ date ne peut rester vide.');

		$this->assert(null !== $this->id_year, 'Aucun exercice spécifié.');
		$this->assert(array_key_exists($this->type, self::TYPES_NAMES), 'Type d\'écriture inconnu : ' . $this->type);
		$this->assert(null === $this->id_creator || $db->test('membres', 'id = ?', $this->id_creator), 'Le membre créateur de l\'écriture n\'existe pas ou plus');

		$is_in_year = $db->test(Year::TABLE, 'id = ? AND start_date <= ? AND end_date >= ?', $this->id_year, $this->date->format('Y-m-d'), $this->date->format('Y-m-d'));

		$this->assert($is_in_year, 'La date ne correspond pas à l\'exercice sélectionné : ' . $this->date->format('d/m/Y'));

		$total = 0;

		$lines = $this->getLines();
		$count = count($lines);

		$this->assert($count > 0, 'Cette écriture ne comporte aucune ligne.');
		$this->assert($count >= 2, 'Cette écriture comporte moins de deux lignes.');
		$this->assert($count == 2 ||  $this->type == self::TYPE_ADVANCED, sprintf('Une écriture de type "%s" ne peut comporter que deux lignes au maximum.', self::TYPES_NAMES[$this->type]));

		$accounts_ids = [];

		foreach ($lines as $k => $line) {
			$this->assert($line->credit || $line->debit, sprintf('Ligne %d: Aucun montant au débit ou au crédit', $k));
			$this->assert($line->credit >= 0 && $line->debit >= 0, sprintf('Ligne %d: Le montant ne peut être négatif', $k));
			$this->assert(($line->credit * $line->debit) === 0 && ($line->credit + $line->debit) > 0, sprintf('Ligne %d: non équilibrée, crédit ou débit doit valoir zéro.', $k));

			$accounts_ids = [$line->id_account];
			$total += $line->credit;
			$total -= $line->debit;
		}

		$this->assert(0 === $total, sprintf('Écriture non équilibrée : déséquilibre (%s) entre débits et crédits', Utils::money_format($total)));

		$this->assert($db->test('acc_years', 'id = ?', $this->id_year), 'L\'exercice sélectionné n\'existe pas');
		$this->assert($this->id_creator === null || $db->test('membres', 'id = ?', $this->id_creator), 'Le compte membre créateur de l\'écriture n\'existe pas');

		$found_accounts = $db->getAssoc(sprintf('SELECT id, id FROM acc_accounts WHERE %s AND id_chart = (SELECT id_chart FROM acc_years WHERE id = %d);', $db->where('id', $accounts_ids), $this->id_year));

		$diff = array_diff($accounts_ids, $found_accounts);
		$this->assert(count($diff) == 0, sprintf('Certains comptes (%s) ne sont pas liés au bon plan comptable', implode(', ', $diff)));

		$this->assert(!$this->id_related || $db->test('acc_transactions', 'id = ?', $this->id_related), 'L\'écriture liée indiquée n\'existe pas');
		$this->assert(!$this->id_related || !$this->exists() || $this->id_related != $this->id, 'Il n\'est pas possible de lier une écriture à elle-même');

		parent::selfCheck();
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

		if (empty($source[$key]) || !count($source[$key])) {
			throw new ValidationException('Aucun compte de dépôt n\'a été sélectionné');
		}

		$account = key($source[$key]);

		$line = new Line;
		$line->importForm([
			'debit'      => $amount,
			'credit'     => 0,
			'id_account' => $account,
		]);

		$this->addLine($line);

		$this->importForm($source);
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['id_related']) && empty($source['id_related'])) {
			$source['id_related'] = null;
		}

		// Transpose lines (HTML transaction forms)
		if (!empty($source['lines']) && is_array($source['lines']) && is_string(key($source['lines']))) {
			try {
				$source['lines'] = Utils::array_transpose($source['lines']);
			}
			catch (\InvalidArgumentException $e) {
				throw new ValidationException('Aucun compte sélectionné pour certaines lignes.');
			}

			unset($source['_lines']);
		}

		if (isset($source['type'])) {
			$this->set('type', (int)$source['type']);
		}

		// Simple two-lines transaction
		if (isset($source['amount']) && $this->type != self::TYPE_ADVANCED && isset($this->type)) {
			if (empty($source['amount'])) {
				throw new ValidationException('Montant non précisé');
			}

			$accounts = $this->getTypesDetails($source)[$this->type]->accounts;

			foreach ($accounts as $account) {
				if (empty($account->selector_value)) {
					throw new ValidationException(sprintf('%s : aucun compte n\'a été sélectionné', $account->label));
				}
			}

			$line = [
				'id_analytical' => $source['id_analytical'] ?? null,
				'reference' => $source['payment_reference'] ?? null,
			];

			$source['lines'] = [
				$line + [$accounts[0]->direction => $source['amount'], 'account_selector' => $accounts[0]->selector_value],
				$line + [$accounts[1]->direction => $source['amount'], 'account_selector' => $accounts[1]->selector_value],
			];

			unset($line, $accounts, $account, $source['simple']);
		}

		// Add lines
		if (isset($source['lines']) && is_array($source['lines'])) {
			$this->resetLines();

			foreach ($source['lines'] as $i => $line) {
				if (empty($line['account']) && empty($line['id_account']) && empty($line['account_selector'])) {
					throw new ValidationException(sprintf('Ligne %d : aucun compte n\'a été sélectionné', $i + 1));
				}

				$l = new Line;
				$l->importForm($line);
				$this->addLine($l);
			}
		}

		return parent::importForm($source);
	}

	public function importFromNewForm(?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		$type = $source['type'] ?? ($this->type ?? self::TYPE_ADVANCED);

		if (self::TYPE_ADVANCED != $type) {
			if (!isset($source['amount'])) {
				throw new UserException('Montant non précisé');
			}
		}

		$this->importForm($source);
	}

	public function importFromEditForm(?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (empty($source['id_related'])) {
			unset($source['id_related']);
		}

		$this->importFromNewForm($source);
	}

	public function importFromPayoffForm(?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (empty($this->_related)) {
			throw new \LogicException('Cannot import pay-off if no related transaction is set');
		}

		// Just make sure we can't trigger importFromNewForm
		unset($source['type'], $source['lines']);

		if (empty($source['amount'])) {
			throw new ValidationException('Montant non précisé');
		}

		if (empty($source['account']) || !is_array($source['account'])) {
			throw new ValidationException('Aucun compte de règlement sélectionné.');
		}

		$id_account = null;
		// Reverse direction (compared with debt/credit transaction)
		$d1 = ($this->_related->type == self::TYPE_DEBT) ? 'credit' : 'debit';
		$d2 = ($d1 == 'credit') ? 'debit' : 'credit';

		foreach ($this->_related->getLines() as $line) {
			if (($this->_related->type == self::TYPE_DEBT && $line->debit)
				|| ($this->_related->type == self::TYPE_CREDIT && $line->credit)) {
				// Skip the type of debt/credit, just keep the thirdparty account
				continue;
			}

			$id_account = $line->id_account;
			break;
		}

		if (!$id_account) {
			throw new \LogicException('Cannot find account ID of related transaction');
		}

		$line = [
			'id_analytical' => $source['id_analytical'] ?? null,
			'reference' => $source['payment_reference'] ?? null,
		];

		$source['lines'] = [
			// First line is third-party account
			$line + compact('id_account') + [$d1 => $source['amount']],
			// Second line is payment account
			$line + ['account_selector' => $source['account'], $d2 => $source['amount']],
		];

		$this->importFromNewForm($source);
	}

	public function importFromBalanceForm(Year $year, ?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		$this->label = 'Balance d\'ouverture';
		$this->date = $year->start_date;
		$this->id_year = $year->id();
		$this->type = self::TYPE_ADVANCED;

		$this->importFromNewForm($source);

		$diff = $this->getLinesCreditSum() - $this->getLinesDebitSum();

		if (!$diff) {
			return;
		}

		// Add final balance line
		$line = new Line;

		if ($diff > 0) {
			$line->debit = $diff;
		}
		else {
			$line->credit = abs($diff);
		}

		$open_account = EntityManager::findOne(Account::class, 'SELECT * FROM @TABLE WHERE id_chart = ? AND type = ? LIMIT 1;', $year->id_chart, Account::TYPE_OPENING);

		if (!$open_account) {
			throw new ValidationException('Aucun compte usuel de bilan d\'ouverture n\'existe dans le plan comptable');
		}

		$line->id_account = $open_account->id();

		$this->addLine($line);
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

		return $db->preparedQuery('REPLACE INTO acc_transactions_users (id_transaction, id_user, id_service_user) VALUES (?, ?, ?);',
			$this->id(), $user_id, $service_id);
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
		$sql = sprintf('SELECT m.id, m.%s AS identity, l.id_service_user FROM membres m INNER JOIN acc_transactions_users l ON l.id_user = m.id WHERE l.id_transaction = ?;', $identity_column);
		return $db->get($sql, $this->id());
	}

	public function listLinkedUsersAssoc()
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$identity_column = Config::getInstance()->get('champ_identite');
		$sql = sprintf('SELECT m.id, m.%s AS identity, l.id_service_user
			FROM membres m
			INNER JOIN acc_transactions_users l ON l.id_user = m.id
			WHERE l.id_transaction = ?;', $identity_column);
		return $db->getAssoc($sql, $this->id());
	}

	public function listRelatedTransactions()
	{
		return EntityManager::getInstance(self::class)->all('SELECT * FROM @TABLE WHERE id_related = ?;', $this->id);
	}

	/**
	 * Return tuples of accounts selectors according to each "simplified" type
	 */
	public function getTypesDetails(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		$details = [
			self::TYPE_REVENUE => [
				'accounts' => [
					[
						'label' => 'Type de recette',
						'targets' => [Account::TYPE_REVENUE],
						'direction' => 'credit',
					],
					[
						'label' => 'Compte d\'encaissement',
						'targets' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
						'direction' => 'debit',
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_REVENUE],
			],
			self::TYPE_EXPENSE => [
				'accounts' => [
					[
						'label' => 'Type de dépense',
						'targets' => [Account::TYPE_EXPENSE],
						'direction' => 'debit',
					],
					[
						'label' => 'Compte de décaissement',
						'targets' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
						'direction' => 'credit',
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
						'direction' => 'credit',
					],
					[
						'label' => 'Vers',
						'targets' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
						'direction' => 'debit',
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_TRANSFER],
				'help' => 'Dépôt en banque, virement interne, etc.',
			],
			self::TYPE_DEBT => [
				'accounts' => [
					[
						'label' => 'Type de dette (dépense)',
						'targets' => [Account::TYPE_EXPENSE],
						'direction' => 'debit',
					],
					[
						'label' => 'Compte de tiers',
						'targets' => [Account::TYPE_THIRD_PARTY],
						'direction' => 'credit',
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_DEBT],
				'help' => 'Quand l\'association doit de l\'argent à un membre ou un fournisseur',
			],
			self::TYPE_CREDIT => [
				'accounts' => [
					[
						'label' => 'Type de créance (recette)',
						'targets' => [Account::TYPE_REVENUE],
						'direction' => 'credit',
					],
					[
						'label' => 'Compte de tiers',
						'targets' => [Account::TYPE_THIRD_PARTY],
						'direction' => 'debit',
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

		// Find out which lines are credit and debit
		$current_accounts = [];

		foreach ($this->getLinesWithAccounts() as $i => $l) {
			if ($l->debit) {
				$current_accounts[] = $l->account_selector;
			}
			elseif ($l->credit) {
				$current_accounts[] = $l->account_selector;
			}

			if (count($current_accounts) == 2) {
				break;
			}
		}

		foreach ($details as $key => &$type) {
			$type = (object) $type;
			$type->id = $key;
			foreach ($type->accounts as $i => &$account) {
				$account = (object) $account;
				$account->targets_string = implode(':', $account->targets);
				$account->selector_name = sprintf('simple[%s][%d]', $key, $i);

				// Try to find out if we can replicate the value
				// debt and credit can have same values, but not others
				// as it can lead to weird stuff
				// exception: revenue/expense can have the same payment account, no issue
				if (($type->id == $this->type)
					|| ($type->id == self::TYPE_CREDIT && $this->type == self::TYPE_DEBT)
					|| ($type->id == self::TYPE_DEBT && $this->type == self::TYPE_CREDIT)
					|| ($type->id == self::TYPE_REVENUE && $this->type == self::TYPE_EXPENSE && $i == 1)
					|| ($type->id == self::TYPE_EXPENSE && $this->type == self::TYPE_REVENUE && $i == 1)
				) {
					$account->selector_value = $source['simple'][$key][$i] ?? ($current_accounts[$i] ?? null);
				}
			}
		}

		unset($account, $type);

		return $details;
	}

	public function payOffFrom(int $id): ?\stdClass
	{
		$this->_related = EntityManager::findOneById(self::class, $id);

		if (!$this->_related) {
			return null;
		}

		$this->id_related = $this->_related->id();
		$this->label = ($this->_related->type == Transaction::TYPE_DEBT ? 'Règlement de dette : ' : 'Règlement de créance : ') . $this->_related->label;
		$this->type = self::TYPE_ADVANCED;

		$out = (object) [
			'id'            => $this->_related->id,
			'amount'        => $this->_related->sum(),
			'id_analytical' => $this->_related->getAnalyticalId(),
		];

		return $out;
	}

	public function getTypeName(): string
	{
		return self::TYPES_NAMES[$this->type];
	}

	public function asDetailsArray(): array
	{
		$lines = [];

		foreach ($this->getLines() as $i => $line) {
			$lines[$i+1] = $line->asDetailsArray();
		}

		return [
			'Numéro'          => $this->id ?? '--',
			'Type'            => self::TYPES_NAMES[$this->type ?? self::TYPE_ADVANCED],
			'Libellé'         => $this->label,
			'Date'            => $this->date,
			'Pièce comptable' => $this->reference,
			'Remarques'       => $this->notes,
			'Total crédit'    => Utils::money_format($this->getLinesCreditSum()),
			'Total débit'     => Utils::money_format($this->getLinesDebitSum()),
			'Lignes'          => $lines,
		];
	}

	public function asJournalArray(): array
	{
		$out = $this->asArray();
		$out['lines'] = $this->getLinesWithAccounts();
		return $out;
	}
}