<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Line;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Utils;
use Garradin\CSV;
use Garradin\DB;
use KD2\DB\EntityManager;

class Reports
{
	static public function getWhereClause(array $criterias, string $transactions_alias = '', string $lines_alias = '', string $accounts_alias = ''): string
	{
		$where = [];
		$db = DB::getInstance();

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

		if (!empty($criterias['type_or_bookmark'])) {
			$criterias['type'] = array_map('intval', (array)$criterias['type_or_bookmark']);
			$where[] = sprintf('(%stype IN (%s) OR %1$sbookmark = 1)', $accounts_alias, implode(',', $criterias['type']));
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

		if (!empty($criterias['project'])) {
			$where[] = sprintf($lines_alias . 'id_project = %d', $criterias['project']);
		}

		if (!empty($criterias['account'])) {
			$where[] = sprintf($accounts_alias . 'id = %d', $criterias['account']);
		}

		if (!empty($criterias['projects_only'])) {
			$where[] = $lines_alias . 'id_project IS NOT NULL';
		}

		if (!empty($criterias['has_type'])) {
			$where[] = $accounts_alias . 'type != 0';
		}

		if (!empty($criterias['before']) && $criterias['before'] instanceof \DateTimeInterface) {
			$where[] = 'date <= ' . $db->quote($criterias['before']->format('Y-m-d'));
		}

		if (!empty($criterias['after']) && $criterias['after'] instanceof \DateTimeInterface) {
			$where[] = 'date >= ' . $db->quote($criterias['after']->format('Y-m-d'));
		}

		if (!count($where)) {
			throw new \LogicException('No criteria was provided.');
		}

		return implode(' AND ', $where);
	}

	static public function countTransactions(array $criterias): int
	{
		$where = self::getWhereClause($criterias);
		return DB::getInstance()->firstColumn('SELECT COUNT(DISTINCT t.id)
			FROM acc_transactions_lines l INNER JOIN acc_transactions t ON t.id = l.id_transaction WHERE ' .$where);
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
		if (!empty($criterias['project']) || !empty($criterias['projects_only'])
			|| !empty($criterias['before']) || !empty($criterias['after'])) {
			$where = self::getWhereClause($criterias, 't', 'l', 'a');
			$sql = self::getBalancesSQL(['inner_select' => 'l.id_project', 'inner_where' => $where]);
			$sql = sprintf('SELECT position, SUM(balance) FROM (%s) GROUP BY position;', $sql);
		}
		else {
			$where = self::getWhereClause($criterias);
			$sql = sprintf('SELECT position, SUM(balance) FROM acc_accounts_balances WHERE %s GROUP BY position;', $where);
		}

		$balances = DB::getInstance()->getAssoc($sql);

		return ($balances[Account::REVENUE] ?? 0) - ($balances[Account::EXPENSE] ?? 0);
	}

	static public function getBalancesSQL(array $parts = [])
	{
		return sprintf('SELECT %s id_year, id, label, code, type, debit, credit, position, %s, is_debt
			FROM (
				SELECT %s t.id_year, a.id, a.label, a.code, a.type,
					SUM(l.credit) AS credit,
					SUM(l.debit) AS debit,
					CASE -- 3 = dynamic asset or liability depending on balance
						WHEN position = 3 AND SUM(l.debit - l.credit) > 0 THEN 1 -- 1 = Asset (actif) comptes fournisseurs, tiers créditeurs
						WHEN position = 3 THEN 2 -- 2 = Liability (passif), comptes clients, tiers débiteurs
						ELSE position
					END AS position,
					CASE
						WHEN position IN (1, 4) -- 1 = asset, 4 = expense
							OR (position = 3 AND SUM(l.debit - l.credit) > 0)
						THEN
							SUM(l.debit - l.credit)
						ELSE
							SUM(l.credit - l.debit)
					END AS balance,
					CASE WHEN SUM(l.debit - l.credit) > 0 THEN 1 ELSE 0 END AS is_debt

				FROM acc_transactions_lines l
				INNER JOIN acc_transactions t ON t.id = l.id_transaction
				INNER JOIN acc_accounts a ON a.id = l.id_account
				%s
				%s
				GROUP BY %s
			)
			%s
			%s
			ORDER BY %s',
			isset($parts['select']) ? $parts['select'] . ',' : '',
			// SUM(balance) is important for grouping projects when id is different but code is the same
			isset($parts['group']) ? 'SUM(balance) AS balance' : 'balance',
			isset($parts['inner_select']) ? $parts['inner_select'] . ',' : '',
			$parts['inner_join'] ?? '',
			isset($parts['inner_where']) ? 'WHERE ' . $parts['inner_where'] : '',
			// Group by account code when multiple years are concerned
			$parts['inner_group'] ?? 'a.code, t.id_year',
			isset($parts['where']) ? 'WHERE ' . $parts['where'] : '',
			isset($parts['group']) ? 'GROUP BY ' . $parts['group'] : '',
			$order ?? 'code'
		);
	}

	/**
	 * Returns SQL query for accounts balances according to $criterias
	 * @param  array       $criterias   List of criterias, see self::getWhereClause
	 * @param  string|null $order       Order of rows (SQL clause), if NULL will order by CODE
	 * @param  bool        $remove_zero Remove accounts where the balance is zero from the list
	 */
	static protected function getAccountsBalancesInnerSQL(array $criterias, ?string $order = null, bool $remove_zero = true): string
	{
		$group = 'code';
		$having = '';

		if ($remove_zero) {
			$having = 'HAVING balance != 0';
		}

		$table = null;

		if (empty($criterias['project']) && empty($criterias['user']) && empty($criterias['creator']) && empty($criterias['subscription'])
			&& empty($criterias['before']) && empty($criterias['after'])) {
			$table = 'acc_accounts_balances';
		}

		// Specific queries that can't rely on acc_accounts_balances
		if (!$table)
		{
			$where = null;

			// The position
			if (!empty($criterias['position'])) {
				$criterias['position'] = (array)$criterias['position'];

				if (in_array(Account::LIABILITY, $criterias['position'])
					|| in_array(Account::ASSET, $criterias['position'])) {
					$where = self::getWhereClause(['position' => $criterias['position']]);
					$criterias['position'][] = Account::ASSET_OR_LIABILITY;
				}
			}

			$inner_where = self::getWhereClause($criterias, 't', 'l', 'a');
			$remove_zero = $remove_zero ? ', ' . $remove_zero : '';
			$inner_group = empty($criterias['year']) ? 'a.code' : null;

			$sql = self::getBalancesSQL(['group' => 'code ' . $having] + compact('order', 'inner_where', 'where', 'inner_group'));
		}
		else {
			$where = self::getWhereClause($criterias);

			$query = 'SELECT id_year, id, label, code, type, SUM(debit) AS debit, SUM(credit) AS credit, position, SUM(balance) AS balance, is_debt FROM %s
				WHERE %s
				GROUP BY %s %s
				ORDER BY %s';

			$sql = sprintf($query, $table, $where, $group, $having, $order);
		}

		return $sql;
	}

	/**
	 * Returns accounts balances according to $criterias
	 * @param  array       $criterias   List of criterias, see self::getWhereClause
	 * @param  string|null $order       Order of rows (SQL clause), if NULL will order by CODE
	 * @param  bool        $remove_zero Remove accounts where the balance is zero from the list
	 */
	static public function getAccountsBalances(array $criterias, ?string $order = null, bool $remove_zero = true): array
	{
		$db = DB::getInstance();
		$order = $order ?: 'code COLLATE NOCASE';

		$sql = self::getAccountsBalancesInnerSQL($criterias, $order, $remove_zero);

		// SQLite does not support OUTER JOIN yet :(
		if (isset($criterias['compare_year'])) {
			$criterias2 = array_merge($criterias, ['year' => $criterias['compare_year']]);
			$sql2 = self::getAccountsBalancesInnerSQL($criterias2, $order, true);

			// Create temporary tables to store data, so that the request is not too complex
			// and doesn't require to do the same SELECTs twice or more
			$table_name = md5(random_bytes(10));
			$db->begin();
			$db->exec(sprintf('
				CREATE TEMP TABLE acc_compare_a_%1$s (id_year, id, label, code, type, debit, credit, position, balance, is_debt);
				CREATE TEMP TABLE acc_compare_b_%1$s (id_year, id, label, code, type, debit, credit, position, balance, is_debt);
				INSERT INTO acc_compare_a_%1$s %2$s;
				INSERT INTO acc_compare_b_%1$s %3$s;',
				$table_name, $sql, $sql2));
			$db->commit();

			// The magic!
			// Here we are selecting the balances of year A, joining with year B
			// BUT to show the accounts used in year B but NOT in year A, we need to do this
			// UNION ALL to select accounts from year B which are NOT in year A
			$sql_union = 'SELECT a.id, a.code AS code, a.label, a.position, a.type, a.debit, a.credit, a.balance, IFNULL(b.balance, 0) AS balance2, IFNULL(a.balance - b.balance, a.balance) AS change
				FROM acc_compare_a_%1$s AS a
				LEFT JOIN acc_compare_b_%1$s AS b ON b.code = a.code AND a.position = b.position AND b.id_year = %2$d
				UNION ALL
				-- Select balances of second year accounts that are =zero in first year
				SELECT
					NULL AS id, c.code AS code, c.label, c.position, c.type, c.debit, c.credit, 0 AS balance, c.balance AS balance2, c.balance * -1 AS change
				FROM acc_compare_b_%1$s AS c
				LEFT JOIN acc_compare_a_%1$s AS d ON d.code = c.code AND d.balance != 0 AND d.position = c.position AND d.id_year = %3$d
				WHERE d.id IS NULL
				ORDER BY code COLLATE NOCASE;';

			$sql = sprintf($sql_union, $table_name, $criterias['compare_year'], $criterias['year']);
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
		$total_left = 'Total charges';
		$total_right = 'Total produits';

		$out->body_left = self::getAccountsBalances($criterias + ['position' => Account::EXPENSE]);
		$out->body_right = self::getAccountsBalances($criterias + ['position' => Account::REVENUE]);

		$out->foot_left = [self::getTotalLine($out->body_left, $total_left)];
		$out->foot_right = [self::getTotalLine($out->body_right, $total_right)];

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

	static public function getVolunteeringStatement(array $criterias, \stdClass $general_statement): \stdClass
	{
		$out = new \stdClass;

		$criterias_all = $criterias + ['type' => [Account::TYPE_VOLUNTEERING_EXPENSE, Account::TYPE_VOLUNTEERING_REVENUE]];

		$out->caption_left = 'Emplois des contributions';
		$out->caption_right = 'Sources des contributions';

		$out->body_left = self::getAccountsBalances($criterias_all + ['position' => Account::EXPENSE]);
		$out->body_right = self::getAccountsBalances($criterias_all + ['position' => Account::REVENUE]);

		$out->foot_left = [
			self::getTotalLine($out->body_left, 'Total emplois'),
			self::getTotalLine(array_merge($out->body_left, $general_statement->body_left), 'Total charges et emplois'),
		];
		$out->foot_right = [
			self::getTotalLine($out->body_right, 'Total sources'),
			self::getTotalLine(array_merge($out->body_right, $general_statement->body_right), 'Total produits et sources'),
		];

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
	 * @return array list of accounts grouped by type
	 */
	static public function getClosingSumsFavoriteAccounts(array $criterias): array
	{
		$types = Account::COMMON_TYPES;
		$accounts = self::getAccountsBalances($criterias + ['type_or_bookmark' => $types], 'type, code COLLATE NOCASE', false);

		$out = [];

		foreach ($types as $type) {
			$out[$type] = (object) [
				'label'    => Account::TYPES_NAMES[$type],
				'type'     => $type,
				'accounts' => [],
			];
		}

		$out[0] = (object) [
			'label'    => 'Autres',
			'type'     => 0,
			'accounts' => [],
		];

		foreach ($accounts as $row) {
			$t = in_array($row->type, $types, true) ? $row->type : 0;
			$out[$t]->accounts[] = $row;
		}

		foreach ($out as $t => $group) {
			if (!count($group->accounts)) {
				unset($out[$t]);
			}
		}

		return $out;
	}

	/**
	 * Grand livre
	 */
	static public function getGeneralLedger(array $criterias): \Generator
	{
		$where = self::getWhereClause($criterias);

		$db = DB::getInstance();

		if (!empty($criterias['projects_only'])) {
			$join = 'acc_projects a ON a.id = l.id_project';
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
