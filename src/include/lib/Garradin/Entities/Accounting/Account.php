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

	const TYPE_APPROPRIATION_RESULT = 13;

	const TYPE_CREDIT_REPORT = 14;
	const TYPE_DEBIT_REPORT = 15;

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
		'Affectation du résultat',
		'Report à nouveau créditeur',
		'Report à nouveau débiteur',
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

	protected $_position = [];

	public function selfCheck(): void
	{
		$db = DB::getInstance();

		$this->assert(trim($this->code) !== '', 'Le numéro de compte ne peut rester vide.');
		$this->assert(trim($this->label) !== '', 'L\'intitulé de compte ne peut rester vide.');

		$this->assert(strlen($this->code) <= 20, 'Le numéro de compte ne peut faire plus de 20 caractères.');
		$this->assert(preg_match('/^[a-z0-9_]+$/i', $this->code), 'Le numéro de compte ne peut comporter que des lettres et des chiffres.');
		$this->assert(strlen($this->label) <= 200, 'L\'intitulé de compte ne peut faire plus de 200 caractères.');
		$this->assert(!isset($this->description) || strlen($this->description) <= 2000, 'La description de compte ne peut faire plus de 2000 caractères.');

		$this->assert(!empty($this->id_chart), 'Aucun plan comptable lié');

		$where = 'code = ? AND id_chart = ?';
		$where .= $this->exists() ? sprintf(' AND id != %d', $this->id()) : '';

		if ($db->test(self::TABLE, $where, $this->code, $this->id_chart)) {
			throw new ValidationException(sprintf('Le numéro "%s" est déjà utilisé par un autre compte.', $this->code));
		}

		$this->assert(array_key_exists($this->type, self::TYPES_NAMES), 'Type invalide: ' . $this->type);
		$this->assert(array_key_exists($this->position, self::POSITIONS_NAMES), 'Position invalide');
		$this->assert($this->user === 0 || $this->user === 1);

		$this->checkLocalRules();

		parent::selfCheck();
	}

	public function checkLocalRules(): void
	{
		static $charts_countries = null;

		if (null === $charts_countries) {
			$charts_countries = DB::getInstance()->getAssoc('SELECT id, country FROM acc_charts;');
		}

		if ('FR' != ($charts_countries[$this->id_chart] ?? null)) {
			return;
		}

		$classe = substr($this->code, 0, 1);
		$sous_classe = substr($this->code, 0, 2);

		if ($this->type == self::TYPE_BANK) {
			$this->assert($sous_classe == '51', 'Le numéro d\'un compte de banque doit commencer par 51');
			$this->assert($this->position == self::ASSET_OR_LIABILITY, 'Un compte de banque doit être en position "actif ou passif"');
		}
		elseif ($this->type == self::TYPE_CASH) {
			$this->assert($sous_classe == '53', 'Le numéro d\'un compte de caisse doit commencer par 53');
			$this->assert($this->position == self::ASSET_OR_LIABILITY, 'Un compte de caisse doit être en position "actif ou passif"');
		}
		elseif ($this->type == self::TYPE_OUTSTANDING) {
			$this->assert(substr($this->code, 0, 3) == '511', 'Le numéro d\'un compte d\'attente doit commencer par 511');
			$this->assert($this->position == self::ASSET_OR_LIABILITY, 'Un compte d\'attente doit être en position "actif ou passif"');
		}
		elseif ($this->type == self::TYPE_ANALYTICAL) {
			$this->assert($classe == '9', 'Le numéro d\'un compte analytique doit commencer par 9');
		}
		elseif ($this->type == self::TYPE_THIRD_PARTY) {
			$this->assert($classe == '4', 'Le numéro d\'un compte de tiers doit commencer par 4');
		}
		elseif ($this->type == self::TYPE_EXPENSE) {
			$this->assert($classe == '6', 'Le numéro d\'un compte de dépense doit commencer par 6');
		}
		elseif ($this->type == self::TYPE_REVENUE) {
			$this->assert($classe == '7', 'Le numéro d\'un compte de recettes doit commencer par 7');
		}
		elseif ($this->type == self::TYPE_VOLUNTEERING) {
			$this->assert($sous_classe == '86' || $sous_classe == '87', 'Le numéro d\'un compte de bénévolat doit commencer par 86 ou 87');
		}

		if ($classe == 4) {
			$this->assert($this->position <= self::ASSET_OR_LIABILITY, 'Un compte de tiers doit être en position "actif ou passif"');
		}
		elseif ($this->position == self::EXPENSE && $sous_classe != 86) {
			$this->assert($classe == 6, 'Le numéro d\'un compte de charge doit commencer par 6.');
		}
		elseif ($this->position == self::REVENUE && $sous_classe != 87) {
			$this->assert($classe == 7, 'Le numéro d\'un compte de produit doit commencer par 7.');
		}
		elseif ($classe == 6) {
			$this->assert($this->position == self::EXPENSE || $this->position == self::NONE, 'Un compte de dépense doit être en position "charge"');
		}
		elseif ($classe == 7) {
			$this->assert($this->position == self::REVENUE || $this->position == self::NONE, 'Un compte de recettes doit être en position "produit"');
		}
		elseif ($classe == 9) {
			$this->assert($this->position == self::NONE, 'Un compte analytique ne peut figurer au bilan ou au compte de résultat');
		}
		elseif ($sous_classe == '86' && strlen($this->code) >= 3) {
			$this->assert($this->position == self::EXPENSE, 'Un compte de bénévolat en 86 doit être en position "charge"');
		}
		else if ($sous_classe == '87' && strlen($this->code) >= 3) {
			$this->assert($this->position == self::REVENUE, 'Un compte de bénévolat en 87 doit être en position "produit"');
		}
	}

	public function listJournal(int $year_id, bool $simple = false, ?DateTimeInterface $start = null, ?DateTimeInterface $end = null)
	{
		$db = DB::getInstance();
		$columns = self::LIST_COLUMNS;

		$tables = 'acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			LEFT JOIN acc_accounts b ON b.id = l.id_analytical';
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
			unset($columns['debit']['label'], $columns['credit']['label'], $columns['line_label']);
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
				// Reverse running sum needs the last sum, first
				if ($list->desc && null === $sum) {
					$sum = $this->getSumAtDate($year_id, ($end ?? new \DateTime($row->date))->modify('+1 day')) * -1 * $reverse;
				}
				elseif (!$list->desc) {
					if (null === $sum && $start) {
						$sum = $this->getSumAtDate($year_id, $start) * -1 * $reverse;
					}

					$sum += $row->change;
				}

				$row->sum = $sum;

				if ($list->desc) {
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
		if ($simple && in_array($this->type, [self::TYPE_BANK, self::TYPE_CASH, self::TYPE_OUTSTANDING, self::TYPE_EXPENSE, self::TYPE_THIRD_PARTY])) {
			return false;
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
				$line->amount = (substr($line->amount, 0, 1) == '-' ? -1 : 1) * Utils::moneyToInteger($line->amount);

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

					// Match date, amount and label
					if ($j->date->format('Ymd') == $line->date->format('Ymd')
						&& ($j->credit * -1 == $line->amount || $j->debit == $line->amount)
						&& strtolower($j->label) == strtolower($line->label)) {
						$row->csv = $line;
						$line = null;
						break;
					}
				}
			}

			$lines[$id] = $row;
		}

		unset($line, $row, $j);

		// Second round to match only amount and label
		foreach ($lines as $row) {
			if ($row->csv || !isset($row->journal->debit)) {
				continue;
			}

			$j = $row->journal;

			foreach ($csv as &$line) {
				if (!isset($line->date)) {
					 continue;
				}

				if ($j->date->format('Ymd') == $line->date->format('Ymd')
					&& ($j->credit * -1 == $line->amount || $j->debit == $line->amount)) {
					$row->csv = $line;
					$line = null;
					break;
				}
			}
		}

		unset($j);

		// Then add CSV lines on the right
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

	public function countDepositJournal(int $year_id): int
	{
		 return DB::getInstance()->firstColumn('SELECT COUNT(*)
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE t.id_year = ? AND l.id_account = ? AND l.credit = 0 AND NOT (t.status & ?)
			ORDER BY t.date, t.id;',
			$year_id, $this->id(), Transaction::STATUS_DEPOSIT);
	}

	public function getSum(int $year_id, bool $simple = false): ?\stdClass
	{
		$sum = DB::getInstance()->first('SELECT balance, credit, debit
			FROM acc_accounts_balances
			WHERE id = ? AND id_year = ?;', $this->id(), $year_id);

		return $sum ?: null;
	}


	public function getSumAtDate(int $year_id, DateTimeInterface $date, bool $reconciled_only = false): int
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

	public function save(bool $selfcheck = true): bool
	{
		DB::getInstance()->exec(sprintf('REPLACE INTO config (key, value) VALUES (\'last_chart_change\', %d);', time()));

		return parent::save($selfcheck);
	}
}