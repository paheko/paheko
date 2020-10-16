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
	static public function getWhereClause(array $criterias): string
	{
		$where = [];

		if (!empty($criterias['year'])) {
			$where[] = sprintf('t.id_year = %d', $criterias['year']);
		}

		if (!empty($criterias['position'])) {
			$db = DB::getInstance();
			$where[] = $db->where('position', $criterias['position']);
		}

		if (!empty($criterias['type'])) {
			$db = DB::getInstance();
			$criterias['type'] = array_map('intval', (array)$criterias['type']);
			$where[] = sprintf('a.type IN (%s)', implode(',', $criterias['type']));
		}

		if (!empty($criterias['user'])) {
			$where[] = sprintf('t.id IN (SELECT id_transaction FROM acc_transactions_users WHERE id_user = %d)', $criterias['user']);
		}

		if (!empty($criterias['creator'])) {
			$where[] = sprintf('t.id_creator = %d', $criterias['creator']);
		}

		if (!count($where)) {
			throw new \LogicException('Unknown criteria');
		}

		return implode(' AND ', $where);
	}

	static public function getSumsByInterval(array $criterias, int $interval)
	{
		$where = self::getWhereClause($criterias);

		$db = DB::getInstance();

		$sql = sprintf('SELECT
			strftime(\'%%s\', MIN(date)) / %d AS start_interval,
			strftime(\'%%s\', MAX(date)) / %1$d AS end_interval
			FROM acc_transactions WHERE id_year = %d;',
			$interval, $criterias['year']);

		extract((array)$db->first($sql));

		$out = array_fill_keys(range($start_interval, $end_interval), 0);

		$sql = sprintf('SELECT strftime(\'%%s\', t.date) / %d AS interval, SUM(l.credit) - SUM(l.debit)
			FROM acc_transactions t
			INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
			INNER JOIN acc_accounts a ON a.id = l.id_account
			WHERE %s
			GROUP BY interval;', $interval, $where);

		$data = $db->getAssoc($sql);
		$sum = 0;

		foreach ($out as $k => &$v) {
			if (array_key_exists($k, $data)) {
				$sum += $data[$k];
			}

			$v = $sum;
		}

		return $out;
	}

	static public function getResult(array $criterias): int
	{
		$where = self::getWhereClause($criterias);
		$sql = sprintf('SELECT SUM(l.credit) - SUM(l.debit)
			FROM %s l
			INNER JOIN %s t ON t.id = l.id_transaction
			INNER JOIN %s a ON a.id = l.id_account
			WHERE %s AND a.position = ?;',
			Line::TABLE, Transaction::TABLE, Account::TABLE, $where);

		$db = DB::getInstance();
		$a = $db->firstColumn($sql, Account::REVENUE);
		$b = $db->firstColumn($sql, Account::EXPENSE);

		return (int)$a - abs((int)$b);
	}

	static public function getClosingSumsWithAccounts(array $criterias): array
	{
		$where = self::getWhereClause($criterias);

		// Find sums, link them to accounts
		$sql = sprintf('SELECT a.id, a.code, a.label, a.position, SUM(l.credit) AS credit, SUM(l.debit) AS debit,
			SUM(l.credit) - SUM(l.debit) AS sum
			FROM %s l
			INNER JOIN %s t ON t.id = l.id_transaction
			INNER JOIN %s a ON a.id = l.id_account
			WHERE %s
			GROUP BY l.id_account
			ORDER BY a.code COLLATE NOCASE;',
			Line::TABLE, Transaction::TABLE, Account::TABLE, $where);
		return DB::getInstance()->getGrouped($sql);
	}

	static public function getBalanceSheet(array $criterias): array
	{
		$out = [
			Account::ASSET => [],
			Account::LIABILITY => [],
			'sums' => [
				Account::ASSET => 0,
				Account::LIABILITY => 0,
			],
		];

		$position_criteria = ['position' => [Account::ASSET, Account::LIABILITY, Account::ASSET_OR_LIABILITY]];
		$list = self::getClosingSumsWithAccounts($criterias + $position_criteria);

		foreach ($list as $row) {
			if ($row->sum == 0) {
				// Ignore empty accounts
				continue;
			}

			$sum = $row->sum;
			$row->sum = abs($row->sum);

			if ($row->position == Account::ASSET_OR_LIABILITY) {
				if ($sum < 0) {
					$out[Account::ASSET][] = $row;
				}
				else {
					$out[Account::LIABILITY][] = $row;
				}
			}
			else {
				$out[$row->position][] = $row;
			}
		}

		$result = self::getResult($criterias);

		$out[Account::LIABILITY][] = (object) [
			'id' => null,
			'label' => $result > 0 ? 'Résultat de l\'exercice courant (excédent)' : 'Résultat de l\'exercice courant (perte)',
			'sum' => $result,
		];

		foreach ($out as $position => $rows) {
			if ($position == 'sums') {
				continue;
			}

			$sum = 0;
			foreach ($rows as $row) {
				$sum += $row->sum;
			}

			$out['sums'][$position] = $sum;
		}

		return $out;
	}

	/**
	 * Return list of favorite accounts (accounts with a type), grouped by type, with their current sum
	 * @param  int    $chart_id
	 * @param  int    $year_id
	 * @return \Generator list of accounts grouped by type
	 */
	static public function getClosingSumsFavoriteAccounts(int $chart_id, int $year_id, bool $include_all = false): \Generator
	{
		if ($include_all) {
			// List all accounts, including those with no amount
			$sql = sprintf('SELECT a.id, a.code, a.label, a.description, a.type,
				(SELECT SUM(l.credit) - SUM(l.debit) FROM %s l INNER JOIN %s t ON t.id = l.id_transaction WHERE l.id_account = a.id AND t.id_year = %d) AS sum
				FROM %s a
				WHERE a.id_chart = %d AND a.type != 0
				GROUP BY a.id
				ORDER BY a.code COLLATE NOCASE;',
				Line::TABLE, Transaction::TABLE, $year_id, Account::TABLE, $chart_id);
		}
		else {
			$sql = sprintf('SELECT a.id, a.code, a.label, a.description, a.type,
				SUM(l.credit) - SUM(l.debit) AS sum
				FROM %s a
				INNER JOIN %s t ON t.id = l.id_transaction
				INNER JOIN %s l ON a.id = l.id_account
				WHERE t.id_year = %d
				GROUP BY l.id_account
				ORDER BY a.code COLLATE NOCASE;', Account::TABLE, Transaction::TABLE, Line::TABLE, $year_id);
		}

		$group = null;

		foreach (DB::getInstance()->iterate($sql) as $row) {
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

	static public function getClosingSums(int $year_id): array
	{
		// Find sums, link them to accounts
		$sql = sprintf('SELECT l.id_account, SUM(l.credit) - SUM(l.debit)
			FROM %s l
			INNER JOIN %s t ON t.id = l.id_transaction
			WHERE t.id_year = %d GROUP BY l.id_account;', Line::TABLE, Transaction::TABLE, $year_id);
		return DB::getInstance()->getAssoc($sql);
	}

	/**
	 * Grand livre
	 */
	static public function getGeneralLedger(array $criterias): \Generator
	{
		$where = self::getWhereClause($criterias);

		$db = DB::getInstance();

		$sql = sprintf('SELECT t.id_year, l.id_account, l.debit, l.credit, t.id, t.date, t.reference, l.reference AS line_reference, t.label, l.label AS line_label
			FROM acc_transactions t
			INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
			INNER JOIN acc_accounts a ON a.id = l.id_account
			WHERE %s
			ORDER BY a.code COLLATE NOCASE, t.date, t.id;', $where);

		$account = null;
		$debit = $credit = 0;
		$accounts = null;

		foreach ($db->iterate($sql) as $row) {
			if (null === $accounts) {
				$accounts = $db->getGrouped('SELECT id, code, label FROM acc_accounts WHERE id_chart = (SELECT id_chart FROM acc_years WHERE id = ?);', $row->id_year);
			}

			if (null !== $account && $account->id != $row->id_account) {
				yield $account;
				$account = null;
			}

			if (null === $account) {
				$account = (object) [
					'code'  => $accounts[$row->id_account]->code,
					'label' => $accounts[$row->id_account]->label,
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
		$where = self::getWhereClause($criterias);

		$sql = sprintf('SELECT t.id_year, l.id_account, l.debit, l.credit, t.id, t.date, t.reference, l.reference AS line_reference, t.label, l.label AS line_label FROM acc_transactions t
			INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
			WHERE %s ORDER BY t.date, t.id;', $where);

		$transaction = null;
		$accounts = null;
		$db = DB::getInstance();

		foreach ($db->iterate($sql) as $row) {
			if (null === $accounts) {
				$accounts = $db->getGrouped('SELECT id, code, label FROM acc_accounts WHERE id_chart = (SELECT id_chart FROM acc_years WHERE id = ?);', $row->id_year);
			}

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
				'account_label' => $accounts[$row->id_account]->label,
				'account_code'  => $accounts[$row->id_account]->code,
				'label'         => $row->line_label,
				'reference'     => $row->line_reference,
				'id_account'    => $row->id_account,
				'credit'        => $row->credit,
				'debit'         => $row->debit,
			];
		}

		if (null === $transaction) {
			return;
		}

		yield $transaction;
	}
}
