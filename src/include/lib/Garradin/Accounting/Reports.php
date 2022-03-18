<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Line;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Utils;
use Garradin\DB;
use KD2\DB\EntityManager;

class Reports
{
	static public function getWhereClause(array $criterias, string $transactions_alias = '', string $lines_alias = '', string $accounts_alias = ''): string
	{
		$where = [];

		$transactions_alias = $transactions_alias ? $transactions_alias . '.' : '';
		$lines_alias = $lines_alias ? $lines_alias . '.' : '';
		$accounts_alias = $accounts_alias ? $accounts_alias . '.' : '';

		if (!empty($criterias['year'])) {
			$where[] = sprintf($transactions_alias . 'id_year = %d', $criterias['year']);
		}

		if (!empty($criterias['position'])) {
			$criterias['position'] = array_map('intval', (array)$criterias['position']);
			$where[] = sprintf($accounts_alias . 'position IN (%s)', implode(',', $criterias['position']));
		}

		if (!empty($criterias['exclude_position'])) {
			$criterias['exclude_position'] = array_map('intval', (array)$criterias['exclude_position']);
			$where[] = sprintf($accounts_alias . 'position NOT IN (%s)', implode(',', $criterias['exclude_position']));
		}

		if (!empty($criterias['type'])) {
			$criterias['type'] = array_map('intval', (array)$criterias['type']);
			$where[] = sprintf($accounts_alias . 'type IN (%s)', implode(',', $criterias['type']));
		}

		if (!empty($criterias['exclude_type'])) {
			$criterias['exclude_type'] = array_map('intval', (array)$criterias['exclude_type']);
			$where[] = sprintf($accounts_alias . 'type NOT IN (%s)', implode(',', $criterias['exclude_type']));
		}

		if (!empty($criterias['user'])) {
			$where[] = sprintf($transactions_alias . 'id IN (SELECT id_transaction FROM acc_transactions_users WHERE id_user = %d)', $criterias['user']);
		}

		if (!empty($criterias['creator'])) {
			$where[] = sprintf($transactions_alias . 'id_creator = %d', $criterias['creator']);
		}

		if (!empty($criterias['subscription'])) {
			$where[] = sprintf($transactions_alias . 'id IN (SELECT tu.id_transaction FROM acc_transactions_users tu WHERE id_service_user = %d)', $criterias['subscription']);
		}

		if (!empty($criterias['analytical'])) {
			$where[] = sprintf($lines_alias . 'id_analytical = %d', $criterias['analytical']);
		}

		if (!empty($criterias['account'])) {
			$where[] = sprintf($accounts_alias . 'id = %d', $criterias['account']);
		}

		if (!empty($criterias['analytical_only'])) {
			$where[] = $lines_alias . 'id_analytical IS NOT NULL';
		}

		if (!empty($criterias['has_type'])) {
			$where[] = $accounts_alias . 'type != 0';
		}

		if (!count($where)) {
			throw new \LogicException('Unknown criteria');
		}

		return implode(' AND ', $where);
	}

	/**
	 * Return account sums per year or per account
	 * @param  bool $by_year If true will return accounts grouped by year, if false it will return years grouped by account
	 */
	static public function getAnalyticalSums(bool $by_year = false, bool $order_code = false): \Generator
	{
		$sql = 'SELECT a.label AS account_label, a.description AS account_description, a.id AS id_account,
			a.code AS account_code,
			y.id AS id_year, y.label AS year_label, y.start_date, y.end_date,
			SUM(l.credit - l.debit) AS sum, SUM(l.credit) AS credit, SUM(l.debit) AS debit, 0 AS total,
			(SELECT SUM(l2.credit - l2.debit) FROM acc_transactions_lines l2
				INNER JOIN acc_transactions t2 ON t2.id = l2.id_transaction
				INNER JOIN acc_accounts a2 ON a2.id = l2.id_account
				WHERE a2.position = %d AND l2.id_analytical = l.id_analytical AND t2.id_year = t.id_year) * -1 AS sum_expense,
			(SELECT SUM(l2.credit - l2.debit) FROM acc_transactions_lines l2
				INNER JOIN acc_transactions t2 ON t2.id = l2.id_transaction
				INNER JOIN acc_accounts a2 ON a2.id = l2.id_account
				WHERE a2.position = %d AND l2.id_analytical = l.id_analytical AND t2.id_year = t.id_year) AS sum_revenue
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			INNER JOIN acc_accounts a ON a.id = l.id_analytical
			INNER JOIN acc_years y ON y.id = t.id_year
			GROUP BY %s
			ORDER BY %s;';

		$order = $order_code ? 'a.code COLLATE U_NOCASE' : 'a.label COLLATE U_NOCASE';

		if ($by_year) {
			$group = 'y.id, a.id';
			$order = 'y.start_date DESC, ' . $order;
		}
		else {
			$group = 'a.id, y.id';
			$order = $order . ', y.id';
		}

		$sql = sprintf($sql, Account::EXPENSE, Account::REVENUE, $group, $order);

		$current = null;

		static $sums = ['credit', 'debit', 'sum'];

		$total = function (\stdClass $current, bool $by_year) use ($sums)
		{
			$out = (object) [
				'label' => 'Total',
				'id_account' => $by_year ? null : $current->id,
				'id_year' => $by_year ? $current->id : null,
				'total' => 1,
			];

			foreach ($sums as $s) {
				$out->{$s} = $current->{$s};
			}

			return $out;
		};

		foreach (DB::getInstance()->iterate($sql) as $row) {
			$id = $by_year ? $row->id_year : $row->id_account;

			if (null !== $current && $current->id !== $id) {
				$current->items[] = $total($current, $by_year);

				yield $current;
				$current = null;
			}

			if (null === $current) {
				$current = (object) [
					'id' => $by_year ? $row->id_year : $row->id_account,
					'label' => $by_year ? $row->year_label : ($order_code ? $row->account_code . ' - ' : '') . $row->account_label,
					'description' => !$by_year ? $row->account_description : null,
					'items' => []
				];

				foreach ($sums as $s) {
					$current->$s = 0;
				}
			}

			$row->label = !$by_year ? $row->year_label : ($order_code ? $row->account_code . ' - ' : '') . $row->account_label;
			$current->items[] = $row;

			foreach ($sums as $s) {
				$current->$s += $row->$s;
			}
		}

		if ($current === null) {
			return;
		}

		$current->items[] = $total($current, $by_year);

		yield $current;
	}

	static public function getSumsPerYear(array $criterias): array
	{
		$where = self::getWhereClause($criterias);

		$sql = sprintf('SELECT y.id, y.start_date, y.end_date, y.label, SUM(b.balance) AS balance
			FROM acc_accounts_balances b
			INNER JOIN acc_years y ON y.id = b.id_year
			WHERE %s
			GROUP BY b.id_year ORDER BY y.end_date;', $where);

		return DB::getInstance()->getGrouped($sql);
	}

	static public function getSumsByInterval(array $criterias, int $interval)
	{
		$where = self::getWhereClause($criterias, 't', 'l', 'a');
		$where_interval = !empty($criterias['year']) ? sprintf(' WHERE id_year = %d', $criterias['year']) : '';

		$db = DB::getInstance();

		$sql = sprintf('SELECT
			strftime(\'%%s\', MIN(date)) / %d AS start_interval,
			strftime(\'%%s\', MAX(date)) / %1$d AS end_interval
			FROM acc_transactions %s;',
			$interval, $where_interval);

		$result = (array)$db->first($sql);
		extract($result);

		if (!isset($start_interval, $end_interval)) {
			return [];
		}

		$out = array_fill_keys(range($start_interval, $end_interval), 0);

		$sql = sprintf('SELECT strftime(\'%%s\', t.date) / %d AS interval, SUM(l.credit) - SUM(l.debit) AS sum, t.id_year
			FROM acc_transactions t
			INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
			INNER JOIN acc_accounts a ON a.id = l.id_account
			WHERE %s
			GROUP BY %s ORDER BY %3$s;', $interval, $where, isset($criterias['year']) ? 'interval' : 't.id_year, interval');

		$data = $db->getGrouped($sql);
		$sum = 0;
		$year = null;

		foreach ($out as $k => &$v) {
			if (array_key_exists($k, $data)) {
				$row = $data[$k];
				if ($row->id_year != $year) {
					$sum = 0;
					$year = $row->id_year;
				}

				$sum += $data[$k]->sum;
			}

			$v = $sum;
		}

		unset($v);

		return $out;
	}

	static public function getResult(array $criterias): int
	{
		if (!empty($criterias['analytical']) || !empty($criterias['analytical_only'])) {
			$table = 'acc_accounts_projects_balances';
		}
		else {
			$table = 'acc_accounts_balances';
		}

		$where = self::getWhereClause($criterias);
		$sql = sprintf('SELECT IFNULL((SELECT SUM(balance) FROM %1$s WHERE %2$s AND position = ?), 0)
			- IFNULL((SELECT SUM(balance) FROM %1$s WHERE %2$s AND position = ?), 0);', $table, $where);

		$db = DB::getInstance();
		return $db->firstColumn($sql, Account::REVENUE, Account::EXPENSE);
	}

	/**
	 * Returns accounts balances according to $criterias
	 * @param  array       $criterias   List of criterias, see self::getWhereClause
	 * @param  string|null $order       Order of rows (SQL clause), if NULL will order by CODE
	 * @param  bool        $remove_zero Remove accounts where the balance is zero from the list
	 */
	static public function getAccountsBalances(array $criterias, ?string $order = null, bool $remove_zero = true)
	{
		$order = $order ?: 'code COLLATE NOCASE';
		$remove_zero = $remove_zero ? 'code HAVING balance != 0' : '';
		$table = null;

		if (!empty($criterias['analytical'])) {
			$table = 'acc_accounts_projects_balances';
		}
		elseif (empty($criterias['user']) && empty($criterias['creator']) && empty($criterias['subscription'])) {
			$table = 'acc_accounts_balances';
		}

		// Specific queries that can't rely on acc_accounts_balances
		if (!$table)
		{
			$where = self::getWhereClause($criterias, 't', 'l', 'a');
			$remove_zero = $remove_zero ? ', ' . $remove_zero : '';

			$query = 'SELECT a.code, a.id, a.label, a.position, SUM(l.credit) AS credit, SUM(l.debit) AS debit,
				CASE WHEN position IN (1, 4) -- 1 = asset, 4 = expense
					OR (position = 3 AND (debit - credit) > 0)
					THEN SUM(l.debit - l.credit)
				ELSE
					SUM(l.credit - l.debit)
				END AS balance
				FROM %s l
				INNER JOIN %s t ON t.id = l.id_transaction
				INNER JOIN %s a ON a.id = l.id_account
				WHERE %s
				GROUP BY l.id_account %s
				ORDER BY %s';

			$sql = sprintf($query, Line::TABLE, Transaction::TABLE, Account::TABLE, $where, $remove_zero, $order);
		}
		else {
			$where = self::getWhereClause($criterias);
			$remove_zero = $remove_zero ? 'GROUP BY ' . $remove_zero : '';

			$query = 'SELECT * FROM %s
				WHERE %s
				%s
				ORDER BY %s';

			$sql = sprintf($query, $table, $where, $remove_zero, $order);
		}


		$db = DB::getInstance();

		// SQLite does not support OUTER JOIN yet :(
		if (isset($criterias['compare_year'])) {
			$sql2 = 'SELECT a.id, a.code AS code, a.label, a.position, a.type, a.debit, a.credit, a.balance, IFNULL(b.balance, 0) AS balance2, IFNULL(a.balance - b.balance, a.balance) AS change
				FROM (%s) AS a
				LEFT JOIN %s b ON b.code = a.code AND a.position = b.position AND b.id_year = %4$d
				UNION ALL
				-- Select balances of second year accounts that are =zero in first year
				SELECT
					NULL AS id, c.code AS code, c.label, c.position, c.type, c.debit, c.credit, 0 AS balance, c.balance AS balance2, c.balance * -1 AS change
				FROM %2$s c
				LEFT JOIN %2$s d ON d.code = c.code AND d.id_year = %3$d AND d.balance != 0 AND d.position = c.position
				WHERE d.id IS NULL AND c.id_year = %4$d AND c.position = %5$d AND c.balance != 0
				ORDER BY code COLLATE NOCASE;';

			$sql = sprintf($sql2, $sql, $table, $criterias['year'], $criterias['compare_year'], $criterias['position']);
		}

		$out = $db->get($sql);

		return $out;
	}

	static public function getTrialBalance(array $criterias, bool $simple = false): \Iterator
	{
		unset($criterias['compare_year']);
		$out = self::getAccountsBalances($criterias, null, false);

		$sums = [
			'debit'      => 0,
			'credit'     => 0,
			'balance'    => null,
			'label'      => 'Total',
		];

		foreach ($out as $row) {
			if (!$simple) {
				$row->balance = $row->debit - $row->credit;
			}

			$sums['debit'] += $row->debit;
			$sums['credit'] += $row->credit;
			yield $row;
		}

		yield (object) $sums;
	}

	/**
	 * Return a table line with the year result
	 */
	static public function getResultLine(array $criterias): \stdClass
	{
		$balance = self::getResult($criterias);
		$balance2 = null;
		$change = null;
		$label = $balance > 0 ? 'Résultat de l\'exercice courant (excédent)' : 'Résultat de l\'exercice courant (perte)';

		if (!empty($criterias['compare_year'])) {
			$balance2 = self::getResult(array_merge($criterias, ['year' => $criterias['compare_year']]));
			$change = $balance - $balance2;
		}

		if (!empty($criterias['compare_year']) || $balance == 0) {
			$label = 'Résultat de l\'exercice';
		}

		return (object) compact('balance', 'balance2', 'label', 'change');
	}

	/**
	 * Return a table line with totals
	 */
	static public function getTotalLine(array $rows, string $label = 'Total'): \stdClass
	{
		$balance = 0;
		$balance2 = 0;
		$change = 0;

		foreach ($rows as $row) {
			$balance += $row->balance;
			$balance2 += $row->balance2 ?? 0;
			$change += $row->change ?? 0;
		}

		return (object) compact('label', 'balance', 'balance2', 'change');
	}

	/**
	 * Statement / Compte de résultat
	 */
	static public function getStatement(array $criterias): \stdClass
	{
		$out = new \stdClass;

		$out->caption_left = 'Charges';
		$out->caption_right = 'Produits';

		$out->body_left = self::getAccountsBalances($criterias + ['position' => Account::EXPENSE]);
		$out->body_right = self::getAccountsBalances($criterias + ['position' => Account::REVENUE]);

		$out->foot_left = [self::getTotalLine($out->body_left, 'Total charges')];
		$out->foot_right = [self::getTotalLine($out->body_right, 'Total produits')];

		$r = self::getResultLine($criterias);

		if ($r->balance < 0) {
			// Deficit should go to expense column
			$out->foot_left[] = $r;
		}
		else {
			$out->foot_right[] = $r;
		}

		return $out;
	}

	/**
	 * Bilan / Balance sheet
	 */
	static public function getBalanceSheet(array $criterias): \stdClass
	{
		$out = new \stdClass;

		$out->caption_left = 'Actif';
		$out->caption_right = 'Passif';

		$out->body_left = self::getAccountsBalances($criterias + ['position' => Account::ASSET]);
		$out->body_right = self::getAccountsBalances($criterias + ['position' => Account::LIABILITY]);

		// Append result to liability
		$r = self::getResultLine($criterias);
		$out->body_right[] = $r;

		// Calculate the total sum for assets and liabilities
		$out->foot_left = [self::getTotalLine($out->body_left, 'Total actif')];
		$out->foot_right = [self::getTotalLine($out->body_right, 'Total passif')];

		return $out;
	}

	/**
	 * Return list of favorite accounts (accounts with a type), grouped by type, with their current sum
	 * @return \Generator list of accounts grouped by type
	 */
	static public function getClosingSumsFavoriteAccounts(array $criterias): \Generator
	{
		$types = [Account::TYPE_EXPENSE, Account::TYPE_REVENUE, Account::TYPE_BANK, Account::TYPE_OUTSTANDING, Account::TYPE_CASH, Account::TYPE_THIRD_PARTY, Account::TYPE_VOLUNTEERING];
		$accounts = self::getAccountsBalances($criterias + ['type' => $types], 'type, code COLLATE NOCASE', false);

		$group = null;

		foreach ($accounts as $row) {
			if (null !== $group && $row->type !== $group->type) {
				yield $group;
				$group = null;
			}

			if (null === $group) {
				$group = (object) [
					'label'    => Account::TYPES_NAMES[$row->type],
					'type'     => $row->type,
					'accounts' => []
				];
			}

			$group->accounts[] = $row;
		}

		if (null !== $group) {
			yield $group;
		}
	}

	/**
	 * Grand livre
	 */
	static public function getGeneralLedger(array $criterias): \Generator
	{
		$where = self::getWhereClause($criterias);

		$db = DB::getInstance();

		if (!empty($criterias['analytical_only'])) {
			$join = 'acc_accounts a ON a.id = l.id_analytical';
		}
		else {
			$join = 'acc_accounts a ON a.id = l.id_account';
		}

		$sql = sprintf('SELECT
			t.id_year, a.id AS id_account, t.id, t.date, t.reference,
			l.debit, l.credit, l.reference AS line_reference, t.label, l.label AS line_label,
			a.label AS account_label, a.code AS account_code
			FROM acc_transactions t
			INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
			INNER JOIN %s
			WHERE %s
			ORDER BY a.code COLLATE U_NOCASE, t.date, t.id;', $join, $where);

		$account = null;
		$debit = $credit = 0;

		foreach ($db->iterate($sql) as $row) {
			if (null !== $account && $account->id != $row->id_account) {
				yield $account;
				$account = null;
			}

			if (null === $account) {
				$account = (object) [
					'code'  => $row->account_code,
					'label' => $row->account_label,
					'id'    => $row->id_account,
					'id_year' => $row->id_year,
					'sum'   => 0,
					'debit' => 0,
					'credit'=> 0,
					'lines' => [],
				];
			}

			$row->date = \DateTime::createFromFormat('Y-m-d', $row->date);

			$account->sum += ($row->credit - $row->debit);
			$account->debit += $row->debit;
			$account->credit += $row->credit;
			$debit += $row->debit;
			$credit += $row->credit;
			$row->running_sum = $account->sum;

			unset($row->account_code, $row->account_label, $row->id_account, $row->id_year);

			$account->lines[] = $row;
		}

		if (null === $account) {
			return;
		}

		$account->all_debit = $debit;
		$account->all_credit = $credit;

		yield $account;
	}

	static public function getJournal(array $criterias): \Generator
	{
		$where = self::getWhereClause($criterias, 't', 'l', 'a');

		$sql = sprintf('SELECT
			t.id_year, l.id_account, l.debit, l.credit, t.id, t.date, t.reference,
			l.reference AS line_reference, t.label, l.label AS line_label,
			a.label AS account_label, a.code AS account_code
			FROM acc_transactions t
			INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
			INNER JOIN acc_accounts a ON l.id_account = a.id
			WHERE %s ORDER BY t.date, t.id;', $where);

		$transaction = null;
		$db = DB::getInstance();

		foreach ($db->iterate($sql) as $row) {
			if (null !== $transaction && $transaction->id != $row->id) {
				yield $transaction;
				$transaction = null;
			}

			if (null === $transaction) {
				$transaction = (object) [
					'id'        => $row->id,
					'label'     => $row->label,
					'date'      => \DateTime::createFromFormat('Y-m-d', $row->date),
					'reference' => $row->reference,
					'lines'     => [],
				];
			}

			$transaction->lines[] = (object) [
				'account_label' => $row->account_label,
				'account_code'  => $row->account_code,
				'label'         => $row->line_label,
				'reference'     => $row->line_reference,
				'id_account'    => $row->id_account,
				'credit'        => $row->credit,
				'debit'         => $row->debit,
				'id_year'       => $row->id_year,
			];
		}

		if (null === $transaction) {
			return;
		}

		yield $transaction;
	}
}
