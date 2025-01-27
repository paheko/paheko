<?php

namespace Paheko\Entities\Accounting;

use KD2\DB\EntityManager;
use KD2\DB\Date;

use Paheko\Config;
use Paheko\DB;
use Paheko\Entity;
use Paheko\Form;
use Paheko\Utils;
use Paheko\UserException;

use Paheko\Users\Users;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;

use Paheko\Accounting\Accounts;
use Paheko\Accounting\Projects;
use Paheko\Accounting\Years;
use Paheko\ValidationException;

use Paheko\UserTemplate\CommonModifiers;

class Transaction extends Entity
{
	use TransactionLinksTrait;
	use TransactionSubscriptionsTrait;
	use TransactionUsersTrait;

	const NAME = 'Écriture';
	const PRIVATE_URL = '!acc/transactions/details.php?id=%d';

	const TABLE = 'acc_transactions';

	const TYPE_ADVANCED = 0;
	const TYPE_REVENUE = 1;
	const TYPE_EXPENSE = 2;
	const TYPE_TRANSFER = 3;
	const TYPE_DEBT = 4;
	const TYPE_CREDIT = 5;

	const STATUS_WAITING = 1;
	const STATUS_PAID = 2;
	const STATUS_DEPOSITED = 4;
	const STATUS_ERROR = 8;
	const STATUS_OPENING_BALANCE = 16;

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

	const LOCKED_PROPERTIES = [
		'label',
		'reference',
		'date',
		'id_year',
		'prev_id',
		'prev_hash',
	];

	const LOCKED_LINE_PROPERTIES = [
		'id_account',
		'debit',
		'credit',
		'label',
		'reference',
	];

	protected ?int $id;
	protected ?int $type = null;
	protected int $status = 0;
	protected string $label;
	protected ?string $notes = null;
	protected ?string $reference = null;

	protected Date $date;

	protected ?string $hash = null;
	protected ?int $prev_id = null;
	protected ?string $prev_hash = null;

	protected int $id_year;
	protected ?int $id_creator = null;

	protected $_lines;
	protected $_old_lines = [];

	protected $_accounts = [];
	protected $_default_selector = [];

	protected Year $_year;

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

	public function findTypeFromAccounts(): int
	{
		if (count($this->getLines()) != 2) {
			return self::TYPE_ADVANCED;
		}

		$types = [];

		foreach ($this->getLinesWithAccounts() as $line) {
			if ($line->account_position == Account::REVENUE && $line->credit) {
				$types[] = self::TYPE_REVENUE;
			}
			elseif ($line->account_position == Account::EXPENSE && $line->debit) {
				$types[] = self::TYPE_EXPENSE;
			}
		}

		// Did not find a expense/revenue account: fall back to advanced
		// (or if one line is expense and the other is revenue)
		if (count($types) != 1) {
			return self::TYPE_ADVANCED;
		}

		return current($types);
	}

	public function getLinesWithAccounts(bool $as_array = false, bool $amount_as_int = true): array
	{
		$db = EntityManager::getInstance(Line::class)->DB();

		// Merge data from accounts with lines
		$accounts = [];
		$projects = [];
		$lines_with_accounts = [];

		foreach ($this->getLines() as $line) {
			if (!array_key_exists($line->id_account, $this->_accounts)) {
				$accounts[] = $line->id_account;
			}

			if ($line->id_project) {
				$projects[] = $line->id_project;
			}
		}

		// Remove NULL accounts
		$accounts = array_filter($accounts);

		if (count($accounts)) {
			$sql = sprintf('SELECT id, label, code, position FROM acc_accounts WHERE %s;', $db->where('id', 'IN', $accounts));
			// Don't use array_merge here or keys will be lost
			$this->_accounts = $this->_accounts + $db->getGrouped($sql);
		}

		if (count($projects)) {
			$projects = $db->getAssoc(sprintf('SELECT id, label FROM acc_projects WHERE %s;', $db->where('id', $projects)));
		}

		foreach ($this->getLines() as &$line) {
			$l = $line->asArray();
			$l['account_code'] = $this->_accounts[$line->id_account]->code ?? null;
			$l['account_label'] = $this->_accounts[$line->id_account]->label ?? null;
			$l['account_position'] = $this->_accounts[$line->id_account]->position ?? null;
			$l['project_name'] = $projects[$line->id_project] ?? null;
			$l['account_selector'] = [$line->id_account => sprintf('%s — %s', $l['account_code'], $l['account_label'])];
			$l['line'] =& $line;

			if (!$as_array) {
				$l = (object) $l;
			}

			if (!$amount_as_int) {
				$l['debit'] = CommonModifiers::money_raw($l['debit']);
				$l['credit'] = CommonModifiers::money_raw($l['credit']);
			}

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

	public function getCreditLine(): ?Line
	{
		if ($this->type == self::TYPE_ADVANCED) {
			return null;
		}

		foreach ($this->getLines() as $line) {
			if ($line->credit) {
				return $line;
			}
		}

		return null;
	}

	public function getDebitLine(): ?Line
	{
		if ($this->type == self::TYPE_ADVANCED) {
			return null;
		}

		foreach ($this->getLines() as $line) {
			if ($line->debit) {
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

	public function getProjectId(): ?int
	{
		$lines = $this->getLines();

		if (!count($lines)) {
			return null;
		}

		$id_project = null;

		foreach ($lines as $line) {
			if ($line->id_project != $id_project) {
				$id_project = $line->id_project;
				break;
			}
		}

		return $id_project;
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

		$copy = ['type', 'label', 'notes', 'reference'];

		foreach ($copy as $field) {
			$new->$field = $this->$field;
		}

		$copy = ['credit', 'debit', 'id_account', 'label', 'reference', 'id_project'];
		$lines = DB::getInstance()->get('SELECT
				l.credit, l.debit, l.label, l.reference, b.id AS id_account, c.id AS id_project
			FROM acc_transactions_lines l
			INNER JOIN acc_accounts a ON a.id = l.id_account
			LEFT JOIN acc_accounts b ON b.code = a.code AND b.id_chart = ?
			LEFT JOIN acc_projects c ON c.id = l.id_project
			WHERE l.id_transaction = ?;',
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

		return $new;
	}

	public function getPaymentReference(): ?string
	{
		foreach ($this->getLines() as $line) {
			if ($line->reference) {
				return $line->reference;
			}
		}

		return null;
	}

	public function setPaymentReference(string $ref): void
	{
		foreach ($this->getLines() as $line) {
			$line->set('reference', $ref);
		}

		if (!isset($line)) {
			$line = new Line;
			$line->set('reference', $ref);
			$this->addLine($line);
		}
	}

	public function getHash(): string
	{
		if (!$this->id_year) {
			throw new \LogicException('Il n\'est pas possible de hasher un mouvement qui n\'est pas associé à un exercice');
		}

		$hash = hash_init('sha256');
		$values = $this->asArray(true);
		$values = array_intersect_key($values, array_flip(self::LOCKED_PROPERTIES));

		hash_update($hash, implode(',', array_keys($values)));
		hash_update($hash, implode(',', $values));

		foreach ($this->getLines() as $line) {
			$values = $line->asArray(true);
			$values = array_intersect_key($values, array_flip(self::LOCKED_LINE_PROPERTIES));

			hash_update($hash, implode(',', array_keys($values)));
			hash_update($hash, implode(',', $values));
		}

		return hash_final($hash, false);
	}

	public function isVerified(): bool
	{
		if (!$this->prev_id) {
			return false;
		}

		if (!$this->prev_hash) {
			return false;
		}

		return $this->verify();
	}

	public function isLocked(): bool
	{
		// locking just got set
		if ($this->hash && array_key_exists('hash', $this->_modified) && $this->_modified['hash'] === null) {
			return false;
		}

		return $this->hash === null ? false : true;
	}

	public function canSaveChanges(): bool
	{
		if (!$this->isLocked()) {
			return true;
		}

		if ($this->isModified('hash')) {
			return false;
		}

		foreach (self::LOCKED_PROPERTIES as $prop) {
			if ($this->isModified($prop)) {
				return false;
			}
		}

		foreach ($this->getLines() as $line) {
			foreach (self::LOCKED_LINE_PROPERTIES as $prop) {
				if ($line->isModified($prop)) {
					return false;
				}
			}
		}

		return true;
	}

	public function assertCanBeModified(): void
	{
		// Allow to change the status
		if (count($this->_modified) === 1 && array_key_exists('status', $this->_modified)) {
			return;
		}

		// We allow to change notes and id_project in a locked transaction
		if (!$this->canSaveChanges()) {
			throw new ValidationException('Il n\'est pas possible de modifier une écriture qui a été verrouillée');
		}

		if (!isset($this->id_year)) {
			return;
		}

		$this->year()->assertCanBeModified();
	}

	public function verify(): bool
	{
		return hash_equals($this->getHash(), $this->hash);
	}

	public function lock(): void
	{
		// Select last locked transaction
		$prev = DB::getInstance()->first('SELECT MAX(id) AS id, hash FROM acc_transactions WHERE hash IS NOT NULL AND id_year = ?;', $this->id_year);

		$this->set('prev_id', $prev->id ?? null);
		$this->set('prev_hash', $prev->hash ?? null);
		$this->set('hash', $this->getHash());
		$this->save();
	}

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
			// Don't use if ($this->exists()) here as the type can be changed on an existing transaction
			if (!$this->hasStatus(self::STATUS_PAID)) {
				$this->addStatus(self::STATUS_WAITING);
			}
		}
		else {
			$this->removeStatus(self::STATUS_WAITING);
		}

		$this->selfCheck();
		$this->assertCanBeModified();

		$lines = $this->getLinesWithAccounts();

		// Self check lines before saving Transaction
		foreach ($lines as $i => $l) {
			$line = $l->line;
			$line->id_transaction = -1; // Get around validation of id_transaction being not null

			if (empty($l->account_code)) {
				throw new ValidationException('Le compte spécifié n\'existe pas.');
			}

			if ($this->type == self::TYPE_EXPENSE && $l->account_position == Account::REVENUE) {
				throw new ValidationException(sprintf('Line %d : il n\'est pas possible d\'attribuer un compte de produit (%s) à une dépense', $i+1, $l->account_code));
			}

			if ($this->type == self::TYPE_REVENUE && $l->account_position == Account::EXPENSE) {
				throw new ValidationException(sprintf('Line %d : il n\'est pas possible d\'attribuer un compte de charge (%s) à une recette', $i+1, $l->account_code));
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

		$db = DB::getInstance();
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

	public function hasStatus(int $property): bool
	{
		return boolval($this->status & $property);
	}

	public function isPaid(): bool
	{
		return $this->hasStatus(self::STATUS_PAID);
	}

	public function markPaid() {
		$this->removeStatus(self::STATUS_WAITING);
		$this->addStatus(self::STATUS_PAID);
	}

	public function markWaiting() {
		$this->removeStatus(self::STATUS_PAID);
		$this->addStatus(self::STATUS_WAITING);
	}

	public function isWaiting(): bool
	{
		if ($this->type !== self::TYPE_DEBT && $this->type !== self::TYPE_CREDIT) {
			return false;
		}

		return $this->hasStatus(self::STATUS_WAITING);
	}

	public function delete(): bool
	{
		if ($this->hash) {
			throw new ValidationException('Il n\'est pas possible de supprimer une écriture qui a été validée');
		}

		$this->year()->assertCanBeModified();

		// FIXME when lettering is properly implemented: mark parent transaction non-deposited when deleting a deposit transaction

		Files::delete($this->getAttachementsDirectory());

		return parent::delete();
	}

	public function selfCheck(): void
	{
		$db = DB::getInstance();

		$this->assert(!empty($this->id_year), 'L\'ID de l\'exercice doit être renseigné.');

		$this->assert(!empty($this->label) && trim((string)$this->label) !== '', 'Le champ libellé ne peut rester vide.');
		$this->assert(strlen($this->label) <= 200, 'Le champ libellé ne peut faire plus de 200 caractères.');
		$this->assert(!isset($this->reference) || strlen($this->reference) <= 200, 'Le champ numéro de pièce comptable ne peut faire plus de 200 caractères.');
		$this->assert(!isset($this->notes) || strlen($this->notes) <= 2000, 'Le champ remarques ne peut faire plus de 2000 caractères.');
		$this->assert(!empty($this->date), 'Le champ date ne peut rester vide.');

		$this->assert(null !== $this->id_year, 'Aucun exercice spécifié.');
		$this->assert(array_key_exists($this->type, self::TYPES_NAMES), 'Type d\'écriture inconnu : ' . $this->type);
		$this->assert(null === $this->id_creator || $db->test('users', 'id = ?', $this->id_creator), 'Le membre créateur de l\'écriture n\'existe pas ou plus');

		$is_in_year = $db->test(Year::TABLE, 'id = ? AND start_date <= ? AND end_date >= ?', $this->id_year, $this->date->format('Y-m-d'), $this->date->format('Y-m-d'));

		if (!$is_in_year) {
			$year = Years::get($this->id_year);
			throw new ValidationException(sprintf('La date (%s) de l\'écriture ne correspond pas à l\'exercice "%s" : la date doit être entre le %s et le %s.',
				Utils::shortDate($this->date),
				$year->label ?? '',
				Utils::shortDate($year->start_date),
				Utils::shortDate($year->end_date)
			));
		}

		$total = 0;

		$lines = $this->getLines();
		$count = count($lines);

		$this->assert($count > 0, 'Cette écriture ne comporte aucune ligne.');
		$this->assert($count >= 2, 'Cette écriture comporte moins de deux lignes.');
		$this->assert($count == 2 ||  $this->type == self::TYPE_ADVANCED, sprintf('Une écriture de type "%s" ne peut comporter que deux lignes au maximum.', self::TYPES_NAMES[$this->type]));

		$chart_id = $db->firstColumn('SELECT id_chart FROM acc_years WHERE id = ?;', $this->id_year);

		$analytical_mandatory = Config::getInstance()->analytical_mandatory;

		foreach ($lines as $k => $line) {
			$k = $k+1;
			$this->assert(!empty($line->id_account), sprintf('Ligne %d: aucun compte n\'est défini', $k));
			$this->assert($line->credit || $line->debit, sprintf('Ligne %d: Aucun montant au débit ou au crédit', $k));
			$this->assert($line->credit >= 0 && $line->debit >= 0, sprintf('Ligne %d: Le montant ne peut être négatif', $k));
			$this->assert(($line->credit * $line->debit) === 0 && ($line->credit + $line->debit) > 0, sprintf('Ligne %d: non équilibrée, crédit ou débit doit valoir zéro.', $k));
			$this->assert($db->test(Account::TABLE, 'id = ? AND id_chart = ?', $line->id_account, $chart_id), sprintf('Ligne %d: le compte spécifié n\'est pas lié au bon plan comptable', $k));

			$total += $line->credit;
			$total -= $line->debit;
		}

		// check that transaction type is respected, or fall back to advanced
		if ($this->type != self::TYPE_ADVANCED) {
			$details = $this->getDetails();

			foreach ($details as $detail) {
				$line = $detail->direction == 'credit' ? $this->getCreditLine() : $this->getDebitLine();
				$this->assert($line !== null, 'Il manque une ligne dans cette écriture');

				$ok = $db->test(Account::TABLE, 'id = ? AND ' . $db->where('type', $detail->types), $line->id_account);

				if (!$ok) {
					$this->set('type', self::TYPE_ADVANCED);
					break;
				}
			}
		}

		$this->assert(0 === $total, sprintf('Écriture non équilibrée : déséquilibre (%s) entre débits et crédits', Utils::money_format($total)));

		// Foreign keys constraints will check for validity of id_creator and id_year

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

		$account = Form::getSelectorValue($source['account_transfer']);

		if (!$account) {
			throw new ValidationException('Aucun compte de dépôt n\'a été sélectionné');
		}

		$line = new Line;
		$line->importForm([
			'debit'      => $amount,
			'credit'     => 0,
			'id_account' => $account,
		]);

		$this->addLine($line);

		$this->importForm($source);
	}

	public function importForm(?array $source = null)
	{
		$source ??= $_POST;

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

		// Check for analytical projects here, and not in selfCheck
		// or we won't be able to create project-less transactions
		// from plugins etc.
		if (self::TYPE_ADVANCED === $this->type
			&& Config::getInstance()->analytical_mandatory
			&& isset($source['lines'])
			&& is_array($source['lines'])) {
			$has_project = false;

			foreach ($source['lines'] as $line) {
				if (!empty($line['id_project'])) {
					$has_project = true;
				}
			}

			$this->assert($has_project, 'Aucun projet analytique n\'a été choisi, hors l\'affectation d\'un projet est obligatoire pour toutes les écritures.');
		}

		// Simple two-lines transaction
		if (isset($source['amount']) && $this->type != self::TYPE_ADVANCED && isset($this->type)) {
			if (empty($source['amount'])) {
				throw new ValidationException('Montant non précisé');
			}

			$accounts = $this->getTypesDetails($source)[$this->type]->accounts;

			// either supply debit/credit keys or simple accounts
			if (!isset($source['debit'], $source['credit'])) {
				foreach ($accounts as $account) {
					if (empty($account->selector_value)) {
						throw new ValidationException(sprintf('%s : aucun compte n\'a été sélectionné', $account->label));
					}
				}
			}

			$line = [
				'reference' => $source['payment_reference'] ?? null,
			];

			$source['lines'] = [
				$line + [
					$accounts[0]->direction => $source['amount'],
					$accounts[1]->direction => 0,
					'account_selector' => $accounts[0]->selector_value,
					'account' => $source[$accounts[0]->direction] ?? null,
				],
				$line + [
					$accounts[1]->direction => $source['amount'],
					$accounts[0]->direction => 0,
					'account_selector' => $accounts[1]->selector_value,
					'account' => $source[$accounts[1]->direction] ?? null,
				],
			];

			if ($this->type != self::TYPE_TRANSFER || Config::getInstance()->analytical_set_all) {
				$source['lines'][0]['id_project'] = $source['id_project'] ?? null;
			}

			if (Config::getInstance()->analytical_set_all) {
				$source['lines'][1]['id_project'] = $source['lines'][0]['id_project'];
			}

			unset($line, $accounts, $account, $source['simple']);
		}

		// Add/update lines objects
		if (isset($source['lines']) && is_array($source['lines'])) {
			$lines = $this->getLines();
			$db = DB::getInstance();

			foreach ($source['lines'] as $i => $line) {
				if (isset($line['account_selector'])) {
					$line['id_account'] = Form::getSelectorValue($line['account_selector']);
				}
				elseif (isset($line['account'])) {
					if (empty($this->id_year) && empty($source['id_year'])) {
						throw new ValidationException('L\'identifiant de l\'exercice comptable n\'est pas précisé.');
					}

					$id_chart = $id_chart ?? $db->firstColumn('SELECT id_chart FROM acc_years WHERE id = ?;', $source['id_year'] ?? $this->id_year);
					$line['id_account'] = $db->firstColumn('SELECT id FROM acc_accounts WHERE code = ? AND id_chart = ?;', $line['account'], $id_chart);

					if (empty($line['id_account'])) {
						throw new ValidationException(sprintf('Le compte avec le code "%s" sur la ligne %d n\'existe pas.', $line['account'], $i+1));
					}
				}

				if (empty($line['id_account'])) {
					throw new ValidationException(sprintf('Ligne %d : aucun compte n\'a été sélectionné', $i + 1));
				}


				if (array_key_exists($i, $lines)) {
					$new = false;
					$l = $lines[$i];
				}
				else {
					$new = true;
					$l = new Line;
				}

				$l->importForm($line);

				if ($l->isModified('debit') || $l->isModified('credit') || $l->isModified('id_account')) {
					$l->set('reconciled', false);
				}

				if ($new) {
					$this->addLine($l);
				}
			}

			// Remove extra lines
			if (count($lines) > count($source['lines'])) {
				$max = count($source['lines']);

				foreach ($lines as $j => $line) {
					if ($j >= $max) {
						$this->_old_lines[] = $line;
						unset($this->_lines[$j]);
					}
				}

				// reset array indexes
				$this->_lines = array_values($this->_lines);
			}
		}

		return parent::importForm($source);
	}

	public function importFromNewForm(?array $source = null): void
	{
		$source ??= $_POST;

		$type = intval($source['type'] ?? ($this->type ?? self::TYPE_ADVANCED));

		if (self::TYPE_ADVANCED !== $type && !isset($source['amount'])) {
			throw new UserException('Montant non précisé');
		}

		$this->importForm($source);
	}

	public function importFromAPI(?array $source = null): void
	{
		$source ??= $_POST;

		if (isset($source['type']) && ctype_alpha($source['type']) && defined(self::class . '::TYPE_' . strtoupper($source['type']))) {
			$source['type'] = constant(self::class . '::TYPE_' . strtoupper($source['type']));
		}

		if (isset($source['id_year'])) {
			$y = $source['id_year'];

			if ($source['id_year'] === 'current') {
				$source['id_year'] = Years::getCurrentOpenYearId();
			}
			elseif ($source['id_year'] === 'match') {
				if (isset($source['date'])) {
					$date = self::filterUserDateValue($source['date']);
				}
				else {
					$date = null;
				}

				$source['id_year'] = Years::getMatchingOpenYearId($date);
			}

			if (!$source['id_year']) {
				throw new UserException(sprintf('Cannot find a valid open year matching "%s"', $y));
			}
		}

		$this->importFromNewForm($source);
	}

	public function importFromPayoffForm(\stdClass $payoff, ?array $source = null): void
	{
		$source ??= $_POST;

		if ($source['type'] == 99) {
			// Just make sure we can't trigger importFromNewForm
			unset($source['lines']);

			$id_project = isset($source['id_project']) ? intval($source['id_project']) : null;
			$source['type'] = self::TYPE_ADVANCED;

			if (!$payoff->multiple) {
				if (empty($source['amount'])) {
					throw new ValidationException('Montant non précisé');
				}

				$amount = Utils::moneyToInteger($source['amount']);

				foreach ($this->getLines() as $line) {
					if ($line->debit != 0) {
						$line->set('debit', $amount);
					}
					else {
						$line->set('credit', $amount);
					}
				}
			}

			if (empty($source['payoff_account']) || !is_array($source['payoff_account'])) {
				throw new ValidationException('Aucun compte de règlement sélectionné.');
			}

			$payoff->payment_line->set('id_account', (int)key($source['payoff_account']));
			$payoff->payment_line->set('reference', $source['payment_reference'] ?? null);
			$payoff->payment_line->set('id_project', $id_project);

			if (Config::getInstance()->analytical_set_all) {
				foreach ($this->getLines() as $line) {
					$line->set('id_project', $id_project);
				}
			}

			$source['lines'] = $this->getLinesWithAccounts(true, false);
		}

		$this->importFromNewForm($source);
	}

	public function importFromBalanceForm(Year $year, ?array $source = null): void
	{
		$source ??= $_POST;

		$this->label = 'Balance d\'ouverture';
		$this->date = $year->start_date;
		$this->id_year = $year->id();
		$this->type = self::TYPE_ADVANCED;
		$this->addStatus(self::STATUS_OPENING_BALANCE);

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
			throw new ValidationException('Aucun compte de bilan d\'ouverture n\'existe dans le plan comptable');
		}

		$line->id_account = $open_account->id();

		$this->addLine($line);
	}

	public function year()
	{
		$this->_year ??= EntityManager::findOneById(Year::class, $this->id_year);
		return $this->_year;
	}

	public function listFiles()
	{
		return Files::list($this->getAttachementsDirectory());
	}

	public function getAttachementsDirectory(): string
	{
		return File::CONTEXT_TRANSACTION . '/' . $this->id();
	}

	public function setDefaultAccount(int $type, string $direction, int $id): void
	{
		$this->_default_selector[$type][$direction] = Accounts::getSelector($id);
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
						'types' => [Account::TYPE_REVENUE],
						'direction' => 'credit',
						'defaults' => [
							self::TYPE_CREDIT => 'credit',
						],
					],
					[
						'label' => 'Compte d\'encaissement',
						'types' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
						'direction' => 'debit',
						'defaults' => [
							self::TYPE_EXPENSE => 'credit',
							self::TYPE_TRANSFER => 'credit',
						],
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_REVENUE],
			],
			self::TYPE_EXPENSE => [
				'accounts' => [
					[
						'label' => 'Type de dépense',
						'types' => [Account::TYPE_EXPENSE],
						'direction' => 'debit',
						'defaults' => [
							self::TYPE_DEBT => 'debit',
						],
					],
					[
						'label' => 'Compte de décaissement',
						'types' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
						'direction' => 'credit',
						'defaults' => [
							self::TYPE_REVENUE => 'debit',
							self::TYPE_TRANSFER => 'credit',
						],
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_EXPENSE],
				'help' => null,
			],
			self::TYPE_TRANSFER => [
				'accounts' => [
					[
						'label' => 'De',
						'types' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
						'direction' => 'credit',
						'defaults' => [
							self::TYPE_EXPENSE => 'credit',
							self::TYPE_REVENUE => 'debit',
						],
					],
					[
						'label' => 'Vers',
						'types' => [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING],
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
						'types' => [Account::TYPE_EXPENSE],
						'direction' => 'debit',
						'defaults' => [
							self::TYPE_EXPENSE => 'debit',
						],
					],
					[
						'label' => 'Compte de tiers',
						'types' => [Account::TYPE_THIRD_PARTY],
						'direction' => 'credit',
						'defaults' => [
							self::TYPE_CREDIT => 'debit',
						],
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_DEBT],
				'help' => 'Quand l\'association doit de l\'argent à un membre ou un fournisseur',
			],
			self::TYPE_CREDIT => [
				'accounts' => [
					[
						'label' => 'Type de créance (recette)',
						'types' => [Account::TYPE_REVENUE],
						'direction' => 'credit',
						'defaults' => [
							self::TYPE_REVENUE => 'credit',
						],
					],
					[
						'label' => 'Compte de tiers',
						'types' => [Account::TYPE_THIRD_PARTY],
						'direction' => 'debit',
						'defaults' => [
							self::TYPE_DEBT => 'credit',
						],
					],
				],
				'label' => self::TYPES_NAMES[self::TYPE_CREDIT],
				'help' => 'Quand un membre ou un client doit de l\'argent à l\'association',
			],
			self::TYPE_ADVANCED => [
				'accounts' => [],
				'label' => self::TYPES_NAMES[self::TYPE_ADVANCED],
				'help' => 'Choisir les comptes du plan comptable, ventiler une écriture sur plusieurs comptes, etc.',
			],
		];

		// Find out which lines are credit and debit
		$current_accounts = [];

		foreach ($this->getLinesWithAccounts() as $l) {
			if ($l->debit) {
				$current_accounts['debit'] = $l->account_selector;
			}
			elseif ($l->credit) {
				$current_accounts['credit'] = $l->account_selector;
			}

			if (count($current_accounts) == 2) {
				break;
			}
		}

		foreach ($details as $key => &$type) {
			$type = (object) $type;
			$type->id = $key;
			foreach ($type->accounts as &$account) {
				$account = (object) $account;
				$account->types_string = implode('|', $account->types);
				$account->selector_name = sprintf('simple[%s][%s]', $key, $account->direction);

				$d = null;

				// Copy selector value for current type
				if ($type->id == $this->type) {
					$d = $account->direction;
				}
				else {
					$d = $account->defaults[$this->type] ?? null;
				}

				if ($d) {
					$account->selector_value = $source['simple'][$key][$d] ?? ($current_accounts[$d] ?? null);
				}

				if (empty($account->selector_value) && isset($this->_default_selector[$key][$account->direction])) {
					$account->selector_value = $this->_default_selector[$key][$account->direction];
				}

				$account->id = isset($account->selector_value) ? key($account->selector_value) : null;
				$account->name = isset($account->selector_value) ? current($account->selector_value) : null;
			}
		}

		unset($account, $type);

		return $details;
	}

	public function getDetails(): ?array
	{
		if ($this->type == self::TYPE_ADVANCED) {
			return null;
		}

		$details = $this->getTypesDetails();

		return [
			'left' => $details[$this->type]->accounts[0],
			'right' => $details[$this->type]->accounts[1],
		];
	}

	public function getTypeName(): string
	{
		return self::TYPES_NAMES[$this->type];
	}

	public function asDetailsArray(bool $modified = false): array
	{
		$lines = [];
		$debit = 0;
		$credit = 0;

		foreach ($this->getLines() as $i => $line) {
			$lines[$i+1] = $line->asDetailsArray();

			$debit += $line->debit;
			$credit +=$line->credit;
		}

		$src = $this->asArray();

		return [
			'Numéro'          => $src['id'] ?? '--',
			'Type'            => self::TYPES_NAMES[$src['type'] ?? self::TYPE_ADVANCED],
			'Libellé'         => $src['label'] ?? null,
			'Date'            => isset($src['date']) ? $src['date']->format('d/m/Y') : null,
			'Pièce comptable' => $src['reference'] ?? null,
			'Remarques'       => $src['notes'] ?? null,
			'Total crédit'    => Utils::money_format($credit),
			'Total débit'     => Utils::money_format($debit),
			'Lignes'          => $lines,
		];
	}

	public function asJournalArray(): array
	{
		$out = $this->asArray();

		if ($this->exists()) {
			$out['url'] = $this->url();
		}

		$out['lines'] = $this->getLinesWithAccounts();
		foreach ($out['lines'] as &$line) {
			unset($line->line);
		}
		unset($line);
		return $out;
	}

	/**
	 * Compare transaction, to see if something has changed
	 */
	public function diff(): ?array
	{
		$out = [
			'transaction' => [],
			'lines' => [],
			'lines_new' => [],
			'lines_removed' => [],
		];

		foreach ($this->_modified as $key => $old) {
			$out['transaction'][$key] = [$old, $this->$key];
		}

		static $keys = [
			'id_account' => 'Numéro de compte',
			'label'      => 'Libellé ligne',
			'reference'  => 'Référence ligne',
			'credit'     => 'Crédit',
			'debit'      => 'Débit',
			'id_project' => 'Projet',
		];

		$new_lines = [];
		$old_lines = [];

		foreach ($this->getLines() as $i => $line) {
			if ($line->exists()) {
				$diff = [];

				foreach ($keys as $key => $label) {
					if ($line->isModified($key)) {
						$diff[$key] = [$line->getModifiedProperty($key), $line->$key];
					}
				}

				if (count($diff)) {
					if (isset($diff['id_project'])) {
						$diff['project'] = [Projects::getName($diff['id_project'][0]), Projects::getName($diff['id_project'][1])];
					}

					if (isset($diff['id_account'])) {
						$diff['account'] = [Accounts::getCodeAndLabel($diff['id_account'][0]), Accounts::getCodeAndLabel($diff['id_account'][1])];
					}
				}

				$l = array_merge($line->asArray(), compact('diff'));

				$l['account'] = Accounts::getCodeAndLabel($l['id_account']);
				$l['project'] = Projects::getName($l['id_project']);

				$out['lines'][$i] = $l;
			}
			else {
				$new_line = [];

				foreach ($keys as $key => $label) {
					$new_line[$key] = $line->$key;
				}

				$new_lines[] = $new_line;
			}
		}

		foreach ($this->_old_lines as $line) {
			$old_line = [];

			foreach ($keys as $key => $label) {
				$old_line[$key] = $line->$key;
			}

			$old_lines[] = $old_line;
		}

		// Append new lines and changed lines
		foreach ($new_lines as $new_line) {
			if (!in_array($new_line, $old_lines)) {
				$new_line['account'] = Accounts::getCodeAndLabel($new_line['id_account']);
				$new_line['project'] = Projects::getName($new_line['id_project']);
				$out['lines_new'][] = $new_line;
			}
		}

		// Append removed lines
		foreach ($old_lines as $old_line) {
			if (!in_array($old_line, $new_lines)) {
				$old_line['account'] = Accounts::getCodeAndLabel($old_line['id_account']);
				$old_line['project'] = Projects::getName($old_line['id_project']);
				$out['lines_removed'][] = $old_line;
			}
		}

		if (!count($out['transaction']) && !count($out['lines']) && !count($out['lines_new']) && !count($out['lines_removed'])) {
			return null;
		}

		return $out;
	}

	public function url(): string
	{
		return Utils::getLocalURL('!acc/transactions/details.php?id=' . $this->id());
	}

	public function getProject(): ?array
	{
		$id = $this->getProjectId();

		if (!$id) {
			return null;
		}

		$name = Projects::getName($id);
		return compact('id', 'name');
	}

	/**
	 * Quick-fill transaction from query parameters
	 */
	public function setDefaultsFromQueryString(Accounts $accounts): ?array
	{
		if (!empty($_POST)) {
			return null;
		}

		$amount = null;
		$id_project = null;
		$lines = [[], []];
		$linked_users = null;

		// a = amount, in single currency units
		if (isset($_GET['a'])) {
			$amount = Utils::moneyToInteger($_GET['a']);
		}

		// a00 = Amount, in cents
		if (isset($_GET['a00'])) {
			$amount = (int)$_GET['a00'];
		}

		// l = label
		if (isset($_GET['l'])) {
			$this->set('label', $_GET['l']);
		}

		// r = reference
		if (isset($_GET['r'])) {
			$this->set('reference', $_GET['r']);
		}

		// n = notes
		if (isset($_GET['n'])) {
			$this->set('notes', $_GET['n']);
		}

		// dt = date
		if (isset($_GET['dt'])) {
			$date = Entity::filterUserDateValue($_GET['dt'], Date::class);

			if (null !== $date && $date instanceof Date) {
				$this->set('date', $date);
			}
		}

		// t = type
		if (isset($_GET['t'])) {
			$this->set('type', (int) $_GET['t']);
		}

		if (isset($_GET['p'])) {
			$id_project = (int) $_GET['p'];
		}

		static $bank_types = [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING];

		// ab = Bank/cash account
		if (isset($_GET['ab'])
			&& ($a = $accounts->getWithCode($_GET['ab']))
			&& in_array($a->type, $bank_types)) {
			$this->setDefaultAccount(self::TYPE_REVENUE, 'debit', $a->id);
			$this->setDefaultAccount(self::TYPE_EXPENSE, 'credit', $a->id);
			$this->setDefaultAccount(self::TYPE_TRANSFER, 'credit', $a->id);
		}

		// ar = Revenue account
		if (isset($_GET['ar'])
			&& ($a = $accounts->getWithCode($_GET['ar']))
			&& $a->type == $a::TYPE_REVENUE) {
			$this->setDefaultAccount(self::TYPE_REVENUE, 'credit', $a->id);
			$this->setDefaultAccount(self::TYPE_CREDIT, 'credit', $a->id);
		}

		// ae = Expense account
		if (isset($_GET['ae'])
			&& ($a = $accounts->getWithCode($_GET['ae']))
			&& $a->type == $a::TYPE_EXPENSE) {
			$this->setDefaultAccount(self::TYPE_EXPENSE, 'debit', $a->id);
			$this->setDefaultAccount(self::TYPE_DEBT, 'debit', $a->id);
		}

		// at = Transfer account
		if (isset($_GET['at'])
			&& ($a = $accounts->getWithCode($_GET['at']))
			&& in_array($a->type, $bank_types)) {
			$this->setDefaultAccount(self::TYPE_TRANSFER, 'debit', $a->id);
		}

		// a3 = Third-party account
		if (isset($_GET['a3'])
			&& ($a = $accounts->getWithCode($_GET['a3']))
			&& $a->type == $a::TYPE_THIRD_PARTY) {
			$this->setDefaultAccount(self::TYPE_CREDIT, 'debit', $a->id);
			$this->setDefaultAccount(self::TYPE_DEBT, 'credit', $a->id);
		}

		// Pre-fill from lllines
		if (isset($_GET['ll']) && is_array($_GET['ll'])) {
			$lines = [];
			foreach ($_GET['ll'] as $l) {
				$lock = $l['k'] ?? null;
				$lines[] = [
					'debit'            => $l['d0'] ?? Utils::moneyToInteger($l['d'] ?? ''),
					'credit'           => $l['c0'] ?? Utils::moneyToInteger($l['c'] ?? ''),
					'debit_locked'     => $lock === 'd' || $lock === 'a',
					'credit_locked'    => $lock === 'c' || $lock === 'a',
					'account_selector' => $accounts->getSelectorFromCode($l['a'] ?? null),
					'label'            => $l['l'] ?? null,
					'reference'        => $l['r'] ?? null,
					'id_project'       => $l['p'] ?? null,
				];
			}

			// Make sure we have at least two lines
			$lines = array_merge($lines, array_fill(0, max(0, 2 - count($lines)), []));
		}

		if (isset($_GET['u'])) {
			$linked_users = [];

			foreach ((array) $_GET['u'] as $value) {
				$id = (int) $value;
				$name = Users::getName($id);

				if ($name) {
					$linked_users[$id] = $name;
				}
			}
		}

		if (isset($_GET['pr']) && !$this->getPaymentReference()) {
			$this->setPaymentReference($_GET['pr']);
		}

		return compact('lines', 'id_project', 'amount', 'linked_users');
	}

	public function saveLinks(?array $source = null): void
	{
		$source ??= $_POST;

		if (empty($source['users'])) {
			$this->deleteLinkedUsers();
		}
		elseif (is_array($source['users']) && count($source['users'])) {
			$this->updateLinkedUsers(array_keys($source['users']));
		}

		if (empty($source['linked'])) {
			$this->deleteLinkedTransactions();
		}
		elseif (is_array($source['linked']) && count($source['linked'])) {
			$this->updateLinkedTransactions(array_keys($source['linked']));
		}
	}
}