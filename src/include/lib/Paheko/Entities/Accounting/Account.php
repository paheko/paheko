<?php

namespace Paheko\Entities\Accounting;

use DateTime;
use KD2\DB\Date;
use Paheko\Config;
use Paheko\CSV_Custom;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Entity;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\ValidationException;
use Paheko\Accounting\Accounts;
use Paheko\Accounting\Charts;

class Account extends Entity
{
	const NAME = 'Compte';
	const PRIVATE_URL = '!acc/charts/accounts/edit.php?id=%d';

	const TABLE = 'acc_accounts';

	const NONE = 0;

	// Actif
	const ASSET = 1;

	// Passif
	const LIABILITY = 2;

	// Passif ou actif
	const ASSET_OR_LIABILITY = 3;

	// Charge
	const EXPENSE = 4;

	// Produit
	const REVENUE = 5;

	const POSITIONS_NAMES = [
		'',
		'Actif',
		'Passif',
		'Actif ou passif',
		'Charge',
		'Produit',
	];

	/**
	 * TYPEs are special kinds of accounts, to help force the account position in the chart
	 */
	const TYPE_NONE = 0;
	const TYPE_BANK = 1;
	const TYPE_CASH = 2;

	/**
	 * Outstanding transaction accounts (like cheque or card payments)
	 */
	const TYPE_OUTSTANDING = 3;
	const TYPE_THIRD_PARTY = 4;

	const TYPE_EXPENSE = 5;
	const TYPE_REVENUE = 6;

	const TYPE_VOLUNTEERING_EXPENSE = 7;
	const TYPE_VOLUNTEERING_REVENUE = 8;

	const TYPE_OPENING = 9;
	const TYPE_CLOSING = 10;

	const TYPE_POSITIVE_RESULT = 11;
	const TYPE_NEGATIVE_RESULT = 12;

	const TYPE_APPROPRIATION_RESULT = 13;

	const TYPE_CREDIT_REPORT = 14;
	const TYPE_DEBIT_REPORT = 15;

	const TYPE_INTERNAL = 16;

	const TYPES_NAMES = [
		'',
		'Banque',
		'Caisse',
		'Attente d\'encaissement',
		'Tiers',
		'Dépenses',
		'Recettes',
		'Bénévolat — Emploi', // Used to be Analytique
		'Bénévolat — Contribution',
		'Ouverture',
		'Clôture',
		'Résultat excédentaire',
		'Résultat déficitaire',
		'Affectation du résultat',
		'Report à nouveau créditeur',
		'Report à nouveau débiteur',
		'Mouvements internes',
	];

	/**
	 * Show only these types of accounts in the quick account view
	 */
	const COMMON_TYPES = [
		self::TYPE_BANK,
		self::TYPE_CASH,
		self::TYPE_OUTSTANDING,
		self::TYPE_INTERNAL,
		self::TYPE_THIRD_PARTY,
		self::TYPE_EXPENSE,
		self::TYPE_REVENUE,
		self::TYPE_VOLUNTEERING_EXPENSE,
		self::TYPE_VOLUNTEERING_REVENUE,
	];

	/**
	 * Positions that should be enforced according to account code
	 */
	const LOCAL_POSITIONS = [
		'FR' => [
			'^1' => self::LIABILITY,
			'^2' => self::ASSET,
			'^3' => self::ASSET,
			'^4' => self::ASSET_OR_LIABILITY,
			'^5' => self::ASSET_OR_LIABILITY,
			'^6' => self::EXPENSE,
			'^7' => self::REVENUE,
			'^86' => self::EXPENSE,
			'^87' => self::REVENUE,
		],
		'BE' => [
			'^69' => self::ASSET_OR_LIABILITY,
			'^6' => self::EXPENSE,
			'^79' => self::ASSET_OR_LIABILITY,
			'^7' => self::REVENUE,
			'^5' => self::ASSET_OR_LIABILITY,
			'^4' => self::ASSET_OR_LIABILITY,
			'^3' => self::ASSET,
			'^2' => self::ASSET,
			'^1' => self::LIABILITY,
		],
		'CH' => [
			'^1' => self::ASSET,
			'^2' => self::LIABILITY,
			'^3(?!910)|^4910' => self::EXPENSE,
			'^4(?!910)|^3910' => self::REVENUE,
			'^5' => self::ASSET_OR_LIABILITY,
		],
	];

	/**
	 * Codes that should be enforced according to type (and vice-versa)
	 */
	const LOCAL_TYPES = [
		'FR' => [
			self::TYPE_BANK => '512',
			self::TYPE_CASH => '53',
			self::TYPE_OUTSTANDING => '511',
			self::TYPE_INTERNAL => '580',
			self::TYPE_THIRD_PARTY => '4',
			self::TYPE_EXPENSE => '6',
			self::TYPE_REVENUE => '7',
			self::TYPE_VOLUNTEERING_EXPENSE => '86',
			self::TYPE_VOLUNTEERING_REVENUE => '87',
			self::TYPE_OPENING => '890',
			self::TYPE_CLOSING => '891',
			self::TYPE_POSITIVE_RESULT => '120',
			self::TYPE_NEGATIVE_RESULT => '129',
			self::TYPE_APPROPRIATION_RESULT => '1068',
			self::TYPE_CREDIT_REPORT => '110',
			self::TYPE_DEBIT_REPORT => '119',
		],
		'BE' => [
			self::TYPE_APPROPRIATION_RESULT => '139',
			self::TYPE_CREDIT_REPORT => '4931',
			self::TYPE_DEBIT_REPORT => '4932',
			self::TYPE_BANK => '56',
			self::TYPE_CASH => '570',
			self::TYPE_OUTSTANDING => '499',
			self::TYPE_EXPENSE => '6',
			self::TYPE_REVENUE => '7',
			self::TYPE_POSITIVE_RESULT => '692',
			self::TYPE_NEGATIVE_RESULT => '690',
			self::TYPE_THIRD_PARTY => '4',
			self::TYPE_OPENING => '890',
			self::TYPE_CLOSING => '891',
		],
		'CH' => [
			self::TYPE_BANK => '102',
			self::TYPE_CASH => '100',
			self::TYPE_OUTSTANDING => '109',
			self::TYPE_THIRD_PARTY => '5',
			self::TYPE_EXPENSE => '3',
			self::TYPE_REVENUE => '4',
			self::TYPE_OPENING => '9100',
			self::TYPE_CLOSING => '9101',
			self::TYPE_POSITIVE_RESULT => '29991',
			self::TYPE_NEGATIVE_RESULT => '29999',
			self::TYPE_APPROPRIATION_RESULT => '2910',
			self::TYPE_CREDIT_REPORT => '2990',
			self::TYPE_DEBIT_REPORT => '2990',
		],
	];

	const LIST_COLUMNS = [
		'id' => [
			'select' => 't.id',
			'label' => 'N°',
		],
		'id_line' => [
			'select' => 'l.id',
		],
		'date' => [
			'label' => 'Date',
			'select' => 't.date',
			'order' => 'date %s, id %1$s',
		],
		'debit' => [
			'select' => 'l.debit',
			'label' => 'Débit',
		],
		'credit' => [
			'select' => 'l.credit',
			'label' => 'Crédit',
		],
		'change' => [
			'select' => '(l.debit - l.credit) * %d',
			'label' => 'Mouvement',
		],
		'sum' => [
			'select' => NULL,
			'label' => 'Solde cumulé',
			'only_with_order' => 'date',
		],
		'reference' => [
			'label' => 'Pièce comptable',
			'select' => 't.reference',
			'order' => 't.reference COLLATE NAT_NOCASE %s',
		],
		'type' => [
			'select' => 't.type',
		],
		'label' => [
			'select' => 't.label',
			'label' => 'Libellé',
			'order' => 't.label COLLATE U_NOCASE %s',
		],
		'line_label' => [
			'select' => 'l.label',
			'label' => 'Libellé ligne'
		],
		'line_reference' => [
			'label' => 'Réf. ligne',
			'select' => 'l.reference',
		],
		'id_project' => [
			'select' => 'l.id_project',
		],
		'project_code' => [
			'select' => 'IFNULL(p.code, SUBSTR(p.label, 1, 10) || \'…\')',
			'label' => 'Projet',
		],
		'locked' => [
			'label' => 'Verrouillée',
			'header_icon' => 'lock',
			'select' => 'CASE WHEN t.hash IS NOT NULL THEN \'Oui\' ELSE \'\' END',
		],
		'files' => [
			'label' => 'Fichiers joints',
			'header_icon' => 'attach',
			'select' => '(SELECT COUNT(tf.id_file) FROM acc_transactions_files tf INNER JOIN files f ON f.id = tf.id_file WHERE tf.id_transaction = t.id AND f.trash IS NULL)',
		],
		'status' => [
			'select' => 't.status',
		],
	];

	const RECONCILE_ALL = 0;
	const RECONCILE_ONLY = 1;
	const RECONCILE_MISSING = 2;

	protected ?int $id;
	protected int $id_chart;
	protected string $code;
	protected string $label;
	protected ?string $description;
	protected int $position = 0;
	protected int $type;
	protected bool $user = false;
	protected bool $bookmark = false;

	protected $_position = [];
	protected ?Chart $_chart = null;

	static protected ?array $_charts;

	public function selfCheck(): void
	{
		$db = DB::getInstance();

		$this->assert(trim($this->code) !== '', 'Le numéro de compte ne peut rester vide.');
		$this->assert(trim($this->label) !== '', 'L\'intitulé de compte ne peut rester vide.');

		// Only enforce code limits if the account is new, or if the code is changed
		if (!$this->exists() || $this->isModified('code')) {
			$this->assert(strlen($this->code) <= 20, 'Le numéro de compte ne peut faire plus de 20 caractères.');
			$this->assert(preg_match('/^[a-z0-9_]+$/i', $this->code), 'Le numéro de compte ne peut comporter que des lettres et des chiffres.');
		}

		$this->assert(strlen($this->label) <= 200, 'L\'intitulé de compte ne peut faire plus de 200 caractères.');
		$this->assert(!isset($this->description) || strlen($this->description) <= 2000, 'La description de compte ne peut faire plus de 2000 caractères.');

		$this->assert(!empty($this->id_chart), 'Aucun plan comptable lié');

		$where = 'code = ? AND id_chart = ?';
		$where .= $this->exists() ? sprintf(' AND id != %d', $this->id()) : '';

		if ($db->test(self::TABLE, $where, $this->code, $this->id_chart)) {
			throw new ValidationException(sprintf('Le numéro "%s" est déjà utilisé par un autre compte.', $this->code));
		}

		$this->assert(isset($this->type));

		$this->checkLocalRules();

		$this->assert(array_key_exists($this->type, self::TYPES_NAMES), 'Type invalide: ' . $this->type);
		$this->assert(array_key_exists($this->position, self::POSITIONS_NAMES), 'Position invalide');

		parent::selfCheck();
	}

	protected function getCountry(): ?string
	{
		if (!isset(self::$_charts)) {
			self::$_charts = DB::getInstance()->getGrouped('SELECT id, country, code FROM acc_charts;');
		}

		return self::$_charts[$this->id_chart]->country ?? null;
	}

	protected function isChartOfficial(): bool
	{
		$country = $this->getCountry();
		return !empty(self::$_charts[$this->id_chart]->code);
	}

	/**
	 * This sets the account position according to local rules
	 * if the chart is linked to a country, but only
	 * if the account is user-created, or if the chart is non-official
	 */
	protected function getLocalPosition(?string $country = null): ?int
	{
		if (!func_num_args()) {
			$country = $this->getCountry();
		}

		$is_official = $this->isChartOfficial();

		if (!$country) {
			return null;
		}

		// Do not change position of official chart accounts
		if (!$this->user && $is_official) {
			return null;
		}

		foreach (self::LOCAL_POSITIONS[$country] as $pattern => $position) {
			if (preg_match('/' . $pattern . '/', $this->code)) {
				return $position;
			}
		}

		return null;
	}

	protected function getLocalType(?string $country = null): int
	{
		if (!func_num_args()) {
			$country = $this->getCountry();
		}

		if (!$country) {
			return self::TYPE_NONE;
		}

		foreach (self::LOCAL_TYPES[$country] as $type => $number) {
			if ($this->matchType($type, $country)) {
				return $type;
			}
		}

		return self::TYPE_NONE;
	}

	protected function matchType(int $type, ?string $country = null): bool
	{
		if (func_num_args() < 2) {
			$country = $this->getCountry();
		}

		$pattern = self::LOCAL_TYPES[$country][$type] ?? null;

		if (!$pattern) {
			return false;
		}

		if (in_array($type, self::COMMON_TYPES)) {
			$pattern = sprintf('/^%s.+/', $pattern);
		}
		else {
			$pattern = sprintf('/^%s$/', $pattern);
		}

		return (bool) preg_match($pattern, $this->code);
	}

	public function setLocalRules(?string $country = null): void
	{
		if (!func_num_args()) {
			$country = $this->getCountry();
		}

		if (!$country) {
			$this->set('type', 0);
			return;
		}

		$this->set('type', $this->getLocalType($country));

		if (null !== ($p = $this->getLocalPosition($country))) {
			// If the allowed local position is asset OR liability, we allow either one of those 3 choices
			if ($p != self::ASSET_OR_LIABILITY
				|| !in_array($this->position, [self::ASSET_OR_LIABILITY, self::ASSET, self::LIABILITY])) {
				$this->set('position', $p);
			}
		}

		if (!isset($this->type)) {
			$this->set('type', 0);
		}
	}

	public function checkLocalRules(): void
	{
		$country = $this->getCountry();

		if ($country === 'FR') {
			$classe = substr($this->code, 0, 1);
			$this->assert($classe >= 1 && $classe <= 8, 'Seuls les comptes de classe 1 à 8 sont autorisés dans le plan comptable français');
		}

		if (!$this->type) {
			return;
		}

		if (!isset(self::LOCAL_TYPES[$country][$this->type])) {
			return;
		}

		$this->assert($this->matchType($this->type), sprintf('Compte "%s - %s" : le numéro des comptes de type "%s" doit commencer par "%s" (%s).', $this->code, $this->label, self::TYPES_NAMES[$this->type], self::LOCAL_TYPES[$country][$this->type], $this->code));
	}

	public function getNewNumberAvailable(?string $base = null): ?string
	{
		$base ??= $this->getNumberBase();

		if (!$base) {
			return $base;
		}

		$pattern = $base . '_%';

		$db = DB::getInstance();
		$used_codes = $db->getAssoc(sprintf('SELECT code, code FROM %s WHERE code LIKE ? AND id_chart = ?;', Account::TABLE), $pattern, $this->id_chart);
		$used_codes = array_values($used_codes);
		$used_codes = array_map(fn($a) => substr($a, strlen($base)), $used_codes);

		$count = $db->count(Account::TABLE, 'id_chart = ? AND code LIKE ?', $this->id_chart, $pattern);
		$letter = null;

		// Make sure we don't reuse an existing code
		while (!$letter || in_array($letter, $used_codes)) {
			// Get new account code, eg. 512A, 99AA, 99BZ etc.
			$letter = Utils::num2alpha($count++);
		}

		return $letter;
	}

	public function getNumberUserPart(): ?string
	{
		$base = $this->getNumberBase();

		if (!$base) {
			return $base;
		}

		return substr($this->code, strlen($base));
	}

	public function getNumberBase(): ?string
	{
		if (!$this->type) {
			return null;
		}

		$country = $this->getCountry();

		if (!isset(self::LOCAL_TYPES[$country][$this->type])) {
			return null;
		}


		return self::LOCAL_TYPES[$country][$this->type];
	}

	public function listJournal(int $year_id, bool $simple = false, ?DateTime $start = null, ?DateTime $end = null)
	{
		$db = DB::getInstance();
		$columns = self::LIST_COLUMNS;

		// Don't show locked column if no transactions are locked
		if (!$db->test('acc_transactions', 'hash IS NOT NULL')) {
			unset($columns['locked']);
		}

		if ($this->type === self::TYPE_BANK) {
			$columns['reconciled'] = [
				'label' => 'Rapprochée',
				'header_icon' => 'check',
				'select' => 'l.reconciled',
			];
		}

		$tables = 'acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			LEFT JOIN acc_projects p ON p.id = l.id_project';
		$conditions = sprintf('l.id_account = %d AND t.id_year = %d', $this->id(), $year_id);

		$sum = null;
		$reverse = $this->isReversed($simple, $year_id) ? -1 : 1;

		if ($start) {
			$conditions .= sprintf(' AND t.date >= %s', $db->quote($start->format('Y-m-d')));
		}

		if ($end) {
			$conditions .= sprintf(' AND t.date <= %s', $db->quote($end->format('Y-m-d')));
		}

		$columns['change']['select'] = sprintf($columns['change']['select'], $reverse);

		if ($simple) {
			unset($columns['debit']['label'], $columns['credit']['label'], $columns['line_label']['label']);
			$columns['line_reference']['label'] = 'Réf. paiement';
		}
		else {
			unset($columns['change']['label']);
		}

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('date', true);
		$list->setCount('COUNT(*)');
		$list->setPageSize(null); // Because with paging we can't calculate the running sum
		$list->setModifier(function (&$row) use (&$sum, &$list, $reverse, $year_id, $start, $end) {
			if (property_exists($row, 'sum')) {
				$desc = $list->getOrderIsDesc();
				// Reverse running sum needs the last sum, first
				if ($desc && null === $sum) {
					$sum = $this->getSumAtDate($year_id, ($end ?? new \DateTime($row->date))->modify('+1 day')) * -1 * $reverse;
				}
				elseif (!$desc) {
					if (null === $sum && $start) {
						$sum = $this->getSumAtDate($year_id, $start) * -1 * $reverse;
					}

					$sum += $row->change;
				}

				$row->sum = $sum;

				if ($desc) {
					$sum -= $row->change;
				}
			}

			$row->date = \DateTime::createFromFormat('!Y-m-d', $row->date);
		});

		$list->setExportCallback(function (&$row) {
			static $columns = ['change', 'sum', 'credit', 'debit'];
			foreach ($columns as $key) {
				if (isset($row->$key)) {
					$row->$key = Utils::money_format($row->$key, '.', '', false);
				}
			}
		});

		return $list;
	}

	/**
	 * Renvoie TRUE si le solde du compte est inversé en vue simplifiée (= crédit - débit, au lieu de débit - crédit)
	 * @return boolean
	 */
	public function isReversed(bool $simple, int $id_year): bool
	{
		$is_reversed = Accounts::isReversed($simple, $this->type);

		if (!$is_reversed) {
			return $is_reversed;
		}

		$position = $this->getPosition($id_year);

		if ($position == self::ASSET || $position == self::EXPENSE) {
			return false;
		}

		return true;
	}

	public function getPosition(int $id_year): int
	{
		$position = $this->_position[$id_year] ?? $this->position;

		if ($position == self::ASSET_OR_LIABILITY) {
			$balance = DB::getInstance()->firstColumn('SELECT debit - credit FROM acc_accounts_balances WHERE id = ? AND id_year = ?;', $this->id, $id_year);
			$position = $balance > 0 ? self::ASSET : self::LIABILITY;
		}

		$this->_position[$id_year] = $position;

		return $position;
	}

	public function hasUnreconciledLinesBefore(int $year_id, DateTime $start_date): bool
	{
		return (bool) DB::getInstance()->firstColumn('SELECT 1 FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE l.id_account = ? AND t.id_year = ? AND l.reconciled = 0 AND t.date < ?
			LIMIT 1;',
			$this->id(), $year_id, Date::createFromInterface($start_date));
	}

	public function getReconcileJournal(int $year_id, DateTime $start_date, DateTime $end_date, int $filter = self::RECONCILE_ALL, bool $desc = false)
	{
		if ($end_date < $start_date) {
			throw new ValidationException('La date de début ne peut être avant la date de fin.');
		}

		$condition = '';

		if ($filter === self::RECONCILE_ONLY) {
			$condition = ' AND l.reconciled = 1';
		}
		elseif ($filter === self::RECONCILE_MISSING) {
			$condition = ' AND l.reconciled = 0';
		}

		$db = DB::getInstance();
		$sql = 'SELECT l.debit, l.credit, t.id, t.date, t.reference, l.reference AS line_reference, t.label, l.label AS line_label, l.reconciled, l.id AS id_line
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE l.id_account = ? AND t.id_year = ? AND t.date >= ? AND t.date <= ? %s
			ORDER BY t.date %s, t.id %1$s;';
		$sql = sprintf($sql, $condition, $desc ? 'DESC' : 'ASC');
		$rows = $db->iterate($sql, $this->id(), $year_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));

		$sum = $this->getSumAtDate($year_id, $start_date);
		$reconciled_sum = $this->getSumAtDate($year_id, $start_date, true);

		$start_sum = false;

		foreach ($rows as $row) {
			if (!$start_sum) {
				yield (object) ['sum' => $sum, 'reconciled_sum' => $reconciled_sum, 'date' => $start_date];
				$start_sum = true;
			}

			$row->date = \DateTime::createFromFormat('Y-m-d', $row->date);
			$sum += ($row->credit - $row->debit);
			$row->running_sum = $sum;

			if ($row->reconciled) {
				$reconciled_sum += ($row->credit - $row->debit);
			}

			$row->reconciled_sum = $reconciled_sum;

			yield $row;
		}

		if (!$filter) {
			yield (object) ['sum' => $sum, 'reconciled_sum' => $reconciled_sum, 'date' => $end_date];
		}
	}

	public function getDepositJournal(int $year_id, array $checked = []): DynamicList
	{
		$columns = [
			'id' => [
				'label' => 'Num.',
				'select' => 't.id',
			],
			'date' => [
				'select' => 't.date',
				'label' => 'Date',
				'order' => 't.date %s, t.id %1$s',
			],
			'reference' => [
				'select' => 't.reference',
				'label' => 'Réf. écriture',
			],
			'line_reference' => [
				'select' => 'l.reference',
				'label' => 'Réf. paiement',
			],
			'label' => [
				'label' => 'Libellé',
				'select' => 't.label',
			],
			'amount' => [
				'label' => 'Montant',
				'select' => 'l.debit',
			],
			'running_sum' => [
				'label' => 'Solde cumulé',
				'only_with_order' => 'date',
				'select' => null,
			],
			'credit' => [
				'select' => 'l.credit',
			],
			'debit' => [
				'select' => 'l.debit',
			],
			'id_account' => [
				'select' => 'l.id_account',
			],
			'id_line' => [
				'select' => 'l.id',
			],
			'id_project' => [
				'select' => 'l.id_project',
			],
		];

		$tables = 'acc_transactions_lines l INNER JOIN acc_transactions t ON t.id = l.id_transaction';
		$conditions = sprintf('t.id_year = %d AND l.id_account = %d AND l.credit = 0 AND NOT (t.status & %d) AND NOT (t.status & %d)',
			$year_id,
			$this->id(),
			Transaction::STATUS_DEPOSITED,
			Transaction::STATUS_OPENING_BALANCE
		);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->setPageSize(null);
		$list->orderBy('date', true);
		$list->setModifier(function (&$row) use (&$sum, $checked) {
			$sum += ($row->credit - $row->debit);
			$row->running_sum = $sum;
			$row->checked = array_key_exists($row->id, $checked);
		});

		return $list;
	}

	public function getDepositMissingBalance(int $year_id): int
	{
		$deposit_balance = DB::getInstance()->firstColumn('SELECT SUM(l.debit)
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE t.id_year = ? AND l.id_account = ? AND l.credit = 0
				AND NOT (t.status & ?)
				AND NOT (t.status & ?)
			ORDER BY t.date, t.id;',
			$year_id,
			$this->id(),
			Transaction::STATUS_DEPOSITED,
			Transaction::STATUS_OPENING_BALANCE
		);

		$account_balance = $this->getSum($year_id)->balance;

		return $account_balance - $deposit_balance;
	}

	public function getSum(int $year_id): ?\stdClass
	{
		$sum = DB::getInstance()->first('SELECT balance, credit, debit
			FROM acc_accounts_balances
			WHERE id = ? AND id_year = ?;', $this->id(), $year_id);

		return $sum ?: null;
	}


	public function getSumAtDate(int $year_id, DateTime $date, bool $reconciled_only = false): int
	{
		$sql = sprintf('SELECT SUM(l.credit) - SUM(l.debit)
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE l.id_account = ? AND t.id_year = ? AND t.date < ? %s;',
			$reconciled_only ? 'AND l.reconciled = 1' : '');
		return (int) DB::getInstance()->firstColumn($sql, $this->id(), $year_id, $date->format('Y-m-d'));
	}

	public function importLimitedForm(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		$data = array_intersect_key($source, array_flip(['type', 'description']));
		parent::import($data);
	}

	public function canDelete(): bool
	{
		if ($this->chart()->code && !$this->user) {
			return false;
		}

		return !DB::getInstance()->firstColumn(sprintf('SELECT 1 FROM %s WHERE id_account = ? LIMIT 1;', Line::TABLE), $this->id());
	}

	/**
	 * An account properties (position, label and code) can only be changed if:
	 * - it's either a user-created account or an account part of a user-created chart
	 * - has no transactions in a closed year
	 */
	public function canEdit(): bool
	{
		if (!$this->exists()) {
			return true;
		}

		$db = DB::getInstance();
		$is_user = $this->user ?: $db->test(Chart::TABLE, 'id = ? AND code IS NULL', $this->id_chart);

		if (!$is_user) {
			return false;
		}

		$sql = sprintf('SELECT 1 FROM %s l
			INNER JOIN %s t ON t.id = l.id_transaction
			INNER JOIN %s y ON y.id = t.id_year
			WHERE l.id_account = ? AND y.status = %d
			LIMIT 1;', Line::TABLE, Transaction::TABLE, Year::TABLE, Year::CLOSED);
		$has_transactions_in_closed_year = $db->firstColumn($sql, $this->id());

		if ($has_transactions_in_closed_year) {
			return false;
		}

		return true;
	}

	/**
	 * We can set account position if:
	 * - account is not in a supported chart country
	 * - account is not part of an official chart
	 * - account is not affected by local position rules
	 */
	public function canSetPosition(): bool
	{
		if (!$this->getCountry()) {
			return true;
		}

		if ($this->isChartOfficial() && !$this->user) {
			return false;
		}

		if ($this->type || $this->getLocalType()) {
			return false;
		}

		if (null !== $this->getLocalPosition()) {
			return false;
		}

		return true;
	}

	/**
	 * We can set account asset or liability if:
	 * - local position rules allow for asset or liability
	 */
	public function canSetAssetOrLiabilityPosition(): bool
	{
		if (!$this->getCountry()) {
			return true;
		}

		if ($this->isChartOfficial() && !$this->user) {
			return false;
		}

		$type = $this->type ?: $this->getLocalType();

		if ($type == self::TYPE_THIRD_PARTY) {
			return true;
		}
		elseif ($type) {
			return false;
		}

		$position = $this->getLocalPosition();

		if ($position == self::ASSET_OR_LIABILITY) {
			return true;
		}

		return false;
	}

	public function chart(): Chart
	{
		$this->_chart ??= Charts::get($this->id_chart);
		return $this->_chart;
	}

	public function save(bool $selfcheck = true): bool
	{
		$this->setLocalRules();
		$ok = parent::save($selfcheck);
		DB::getInstance()->exec(sprintf('REPLACE INTO config (key, value) VALUES (\'last_chart_change\', %d);', time()));
		return $ok;
	}

	public function position_name(): string
	{
		return self::POSITIONS_NAMES[$this->position];
	}

	public function type_name(): string
	{
		return self::TYPES_NAMES[$this->type];
	}

	public function importForm(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['code_value'], $source['code_base'])) {
			$source['code'] = trim($source['code_base']) . trim($source['code_value']);
		}

		parent::importForm($source);
	}

	public function level(): int
	{
		$level = strlen($this->code);

		if ($level > 6) {
			$level = 6;
		}

		return $level;
	}

	public function isListedAsFavourite(): bool
	{
		if ($this->bookmark) {
			return true;
		}

		if ($this->user) {
			return true;
		}

		return DB::getInstance()->test('acc_transactions_lines', 'id_account = ?', $this->id);
	}

	public function createOpeningBalance(Year $year, int $amount, ?string $label = null): Transaction
	{
		$accounts = $year->accounts();
		$opening_account = $accounts->getOpeningAccountId();

		if (!$opening_account) {
			throw new UserException('Impossible de créer la balance d\'ouverture : le plan comptable sélectionné n\'a pas de compte 890 — Balance d\'ouverture.');
		}

		$t = new Transaction;
		$t->label = $label ?? 'Solde d\'ouverture du compte';
		$t->date = clone $year->start_date;
		$t->type = $t::TYPE_ADVANCED;
		$t->notes = 'Créé automatiquement à l\'ajout du compte';
		$t->id_year = $year->id;


		$credit = $amount > 0 ? 0 : abs($amount);
		$debit = $amount < 0 ? 0 : abs($amount);
		$t->addLine(Line::create($this->id(), $credit, $debit));
		$t->addLine(Line::create($opening_account, $debit, $credit));
		return $t;
	}

}
