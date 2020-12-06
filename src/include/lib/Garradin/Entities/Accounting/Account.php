<?php

namespace Garradin\Entities\Accounting;

use DateTimeInterface;
use Garradin\Config;
use Garradin\CSV_Custom;
use Garradin\DB;
use Garradin\DynamicList;
use Garradin\Entity;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\ValidationException;
use Garradin\Accounting\Charts;

class Account extends Entity
{
	const TABLE = 'acc_accounts';

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

	const TYPE_ANALYTICAL = 7;
	const TYPE_VOLUNTEERING = 8;

	const TYPE_OPENING = 9;
	const TYPE_CLOSING = 10;

	const TYPE_POSITIVE_RESULT = 11;
	const TYPE_NEGATIVE_RESULT = 12;

	const TYPES_NAMES = [
		'',
		'Banque',
		'Caisse',
		'Attente d\'encaissement',
		'Tiers',
		'Dépenses',
		'Recettes',
		'Analytique',
		'Bénévolat',
		'Ouverture',
		'Clôture',
		'Résultat excédentaire',
		'Résultat déficitaire',
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
			'select' => '(l.credit - l.debit) * %d',
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
		],
		'type' => [
			'select' => 't.type',
		],
		'label' => [
			'select' => 't.label',
			'label' => 'Libellé',
		],
		'line_label' => [
			'select' => 'l.label',
			'label' => 'Libellé ligne'
		],
		'line_reference' => [
			'label' => 'Réf. ligne',
			'select' => 'l.reference',
		],
		'id_analytical' => [
			'select' => 'l.id_analytical',
		],
		'code_analytical' => [
			'label' => 'Projet',
			'select' => 'b.code',
		],
		'status' => [
			'select' => 't.status',
		],
	];

	protected $id;
	protected $id_chart;
	protected $code;
	protected $label;
	protected $description;
	protected $position;
	protected $type;
	protected $user = 0;

	protected $_types = [
		'id'          => 'int',
		'id_chart'    => 'int',
		'code'        => 'string',
		'label'       => 'string',
		'description' => '?string',
		'position'    => 'int',
		'type'        => 'int',
		'user'        => 'int',
	];

	protected $_form_rules = [
		'code'        => 'required|string|alpha_num|max:10',
		'label'       => 'required|string|max:200',
		'description' => 'string|max:2000',
		'position'    => 'required|numeric|min:0',
		'type'        => 'required|numeric|min:0',
	];

	public function selfCheck(): void
	{
		$db = DB::getInstance();

		$this->assert(!empty($this->id_chart), 'Aucun plan comptable lié');

		$where = 'code = ? AND id_chart = ?';
		$where .= $this->exists() ? sprintf(' AND id != %d', $this->id()) : '';

		if ($db->test(self::TABLE, $where, $this->code, $this->id_chart)) {
			throw new ValidationException(sprintf('Le code "%s" est déjà utilisé par un autre compte.', $this->code));
		}

		$this->assert(array_key_exists($this->type, self::TYPES_NAMES), 'Type invalide');
		$this->assert(array_key_exists($this->position, self::POSITIONS_NAMES), 'Position invalide');
		$this->assert($this->user === 0 || $this->user === 1);

		parent::selfCheck();
	}

	public function listJournal(int $year_id, bool $simple = false)
	{
		$columns = self::LIST_COLUMNS;

		$tables = 'acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			LEFT JOIN acc_accounts b ON b.id = l.id_analytical';
		$conditions = sprintf('l.id_account = %d AND t.id_year = %d', $this->id(), $year_id);

		$sum = 0;
		$reverse = $simple && self::isReversed($this->type) ? -1 : 1;

		if ($simple) {
			unset($columns['debit']['label'], $columns['credit']['label'], $columns['line_label']);
			$columns['line_reference']['label'] = 'Réf. paiement';
			$columns['change']['select'] = sprintf($columns['change']['select'], $reverse);
		}
		else {
			unset($columns['change']);
		}

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('date', false);
		$list->setCount('COUNT(*)');
		$list->setPageSize(null);
		$list->setModifier(function (&$row) use (&$sum, $reverse) {
			if (property_exists($row, 'sum')) {
				$sum += isset($row->change) ? $row->change : ($row->credit - $row->debit);
				$row->sum = $sum;
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

	static public function isReversed(int $type): bool
	{
		return in_array($type, [self::TYPE_BANK, self::TYPE_CASH, self::TYPE_OUTSTANDING, self::TYPE_EXPENSE, self::TYPE_THIRD_PARTY]);
	}

	public function getReconcileJournal(int $year_id, DateTimeInterface $start_date, DateTimeInterface $end_date, bool $only_non_reconciled = false)
	{
		if ($end_date < $start_date) {
			throw new ValidationException('La date de début ne peut être avant la date de fin.');
		}

		$condition = $only_non_reconciled ? ' AND l.reconciled = 0' : '';

		$db = DB::getInstance();
		$sql = 'SELECT l.debit, l.credit, t.id, t.date, t.reference, l.reference AS line_reference, t.label, l.label AS line_label, l.reconciled, l.id AS id_line
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE l.id_account = ? AND t.id_year = ? AND t.date >= ? AND t.date <= ? %s
			ORDER BY t.date, t.id;';
		$rows = $db->iterate(sprintf($sql, $condition), $this->id(), $year_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));

		$sum = $this->getSumAtDate($year_id, $start_date);
		$reconciled_sum = $this->getSumAtDate($year_id, $start_date, true);

		$start_sum = false;

		foreach ($rows as $row) {
			if (!$start_sum) {
				yield (object) ['sum' => $sum, 'date' => $start_date];
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

		if (!$only_non_reconciled) {
			yield (object) ['sum' => $sum, 'reconciled_sum' => $reconciled_sum, 'date' => $end_date];
		}
	}

	public function mergeReconcileJournalAndCSV(\Generator $journal, CSV_Custom $csv)
	{
		$lines = [];

		$csv = iterator_to_array($csv->iterate());
		$journal = iterator_to_array($journal);
		$i = 0;
		$sum = 0;

		foreach ($csv as $k => &$line) {
			try {
				$date = \DateTime::createFromFormat('!d/m/Y', $line->date);
				$line->amount = ($line->amount < 0 ? -1 : 1) * Utils::moneyToInteger($line->amount);

				if (!$date) {
					throw new UserException('Date invalide : ' . $line->date);
				}

				$line->date = $date;
			}
			catch (UserException $e) {
				throw new UserException(sprintf('Ligne %d : %s', $k, $e->getMessage()));
			}
		}
		unset($line);

		foreach ($journal as $j) {
			$id = $j->date->format('Ymd') . '.' . $i++;

			$row = (object) ['csv' => null, 'journal' => $j];

			if (isset($j->debit)) {
				foreach ($csv as &$line) {
					if (!isset($line->date)) {
						 continue;
					}
					if ($j->date->format('Ymd') == $line->date->format('Ymd')
						&& ($j->credit == abs($line->amount) || $j->debit == abs($line->amount))) {
						$row->csv = $line;
						$line = null;
						break;
					}
				}
			}

			$lines[$id] = $row;
		}

		unset($j);

		foreach ($csv as $line) {
			if (null == $line) {
				continue;
			}

			$id = $line->date->format('Ymd') . '.' . ($i++);
			$lines[$id] = (object) ['csv' => $line, 'journal' => null];
		}

		ksort($lines);
		$prev = null;

		foreach ($lines as &$line) {
			$line->add = false;

			if (isset($line->csv)) {
				$sum += $line->csv->amount;
				$line->csv->running_sum = $sum;

				if ($prev && ($prev->date->format('Ymd') != $line->csv->date->format('Ymd') || $prev->label != $line->csv->label)) {
					$prev = null;
				}
			}

			if (isset($line->csv) && isset($line->journal)) {
				$prev = null;
			}

			if (isset($line->csv) && !isset($line->journal) && !$prev) {
				$line->add = true;
				$prev = $line->csv;
			}
		}

		return $lines;
	}

	public function getDepositJournal(int $year_id, array $checked = []): \Generator
	{
		$res = DB::getInstance()->iterate('SELECT l.debit, l.credit, t.id, t.date, t.reference, l.reference AS line_reference, t.label, l.label AS line_label, l.reconciled, l.id AS id_line, l.id_account
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE t.id_year = ? AND l.id_account = ? AND l.credit = 0 AND NOT (t.status & ?)
			ORDER BY t.date, t.id;',
			$year_id, $this->id(), Transaction::STATUS_DEPOSIT);

		$sum = 0;

		foreach ($res as $row) {
			$row->date = \DateTime::createFromFormat('Y-m-d', $row->date);
			$sum += ($row->credit - $row->debit);
			$row->running_sum = $sum;
			$row->checked = array_key_exists($row->id, $checked);
			yield $row;
		}
	}

	public function getSum(int $year_id, bool $simple = false): int
	{
		$sum = (int) DB::getInstance()->firstColumn('SELECT SUM(l.credit) - SUM(l.debit)
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			wHERE l.id_account = ? AND t.id_year = ?;', $this->id(), $year_id);

		if ($simple && self::isReversed($this->type)) {
			$sum *= -1;
		}

		return $sum;
	}


	public function getSumAtDate(int $year_id, DateTimeInterface $date, bool $reconciled_only = false): int
	{
		$sql = sprintf('SELECT SUM(l.credit) - SUM(l.debit)
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			wHERE l.id_account = ? AND t.id_year = ? AND t.date < ? %s;',
			$reconciled_only ? 'AND l.reconciled = 1' : '');
		return (int) DB::getInstance()->firstColumn($sql, $this->id(), $year_id, $date->format('Y-m-d'));
	}

	public function importSimpleForm(array $translate_type_position, array $translate_type_codes, ?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (empty($source['type'])) {
			throw new UserException('Le type est obligatoire dans ce formulaire');
		}

		$type = (int) $source['type'];

		if (array_key_exists($type, $translate_type_position)) {
			$source['position'] = $translate_type_position[$type];
		}
		else {
			$source['position'] = self::ASSET_OR_LIABILITY;
		}

		if (array_key_exists($type, $translate_type_codes)) {
			$source['code'] = $translate_type_codes[$type];
		}

		$this->importForm($source);
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
		return !DB::getInstance()->firstColumn(sprintf('SELECT 1 FROM %s WHERE id_account = ? LIMIT 1;', Line::TABLE), $this->id());
	}

	/**
	 * An account properties (position, label and code) can only be changed if:
	 * * it's either a user-created account or an account part of a user-created chart
	 * * has no transactions in a closed year
	 * @return bool
	 */
	public function canEdit(): bool
	{
		$db = DB::getInstance();
		$sql = sprintf('SELECT 1 FROM %s l
			INNER JOIN %s t ON t.id = l.id_transaction
			INNER JOIN %s y ON y.id = t.id_year
			WHERE l.id_account = ? AND y.closed = 1
			LIMIT 1;', Line::TABLE, Transaction::TABLE, Year::TABLE);
		$has_transactions_in_closed_year = $db->firstColumn($sql, $this->id());

		if ($has_transactions_in_closed_year) {
			return false;
		}

		if ($this->user) {
			return true;
		}

		return $db->test(Chart::TABLE, 'id = ? AND code IS NULL', $this->id_chart);
	}

	public function chart(): Chart
	{
		return Charts::get($this->id_chart);
	}

	public function save(): bool
	{
		Config::getInstance()->set('last_chart_change', time());
		return parent::save();
	}
}