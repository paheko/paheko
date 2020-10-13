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
	static public function getResult(array $criterias): ?int
	{
		$where = self::getWhereClause($criterias);
		$sql = sprintf('SELECT SUM(l.credit) - SUM(l.debit)
			FROM %s l
			INNER JOIN %s t ON t.id = l.id_transaction
			INNER JOIN %s a ON a.id = l.id_account
			WHERE %s AND a.position IN (?, ?);',
			Line::TABLE, Transaction::TABLE, Account::TABLE, $where);
		return DB::getInstance()->firstColumn($sql, Account::EXPENSE, Account::REVENUE);
	}

	static public function getClosingSumsWithAccounts(array $criterias): array
	{
		$where = self::getWhereClause($criterias);

		// Find sums, link them to accounts
		$sql = sprintf('SELECT a.id, a.code, a.label, SUM(l.credit) AS credit, SUM(l.debit) AS debit,
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
		$year = Years::get($year_id);

		if (true || $year->closed) { // FIXME!!!!
			return self::computeClosingSums($year->id());
		}
		else {
			// Get the ID of the account used to store closing sums
			$closing_account_id = $db->firstColumn(sprintf('SELECT id FROM %s WHERE id_chart = ? AND type = ?;', Account::TABLE, $year->id_chart, Account::TYPE_CLOSING));

			// Find sums, link them to accounts
			$sql = sprintf('SELECT b.id_account, SUM(a.credit) - SUM(a.debit)
				FROM %s a
				INNER JOIN %s b ON b.id_transaction = a.id_transaction
				WHERE a.id_account = %d AND a.id_year = %d;', Line::TABLE, Line::TABLE, $closing_account_id, $year_id);
			return DB::getInstance()->getAssoc($sql);
		}
	}

	static protected function computeClosingSums(int $year_id): array
	{
		// Find sums, link them to accounts
		$sql = sprintf('SELECT l.id_account, SUM(l.credit) - SUM(l.debit)
			FROM %s l
			INNER JOIN %s t ON t.id = l.id_transaction
			WHERE t.id_year = %d GROUP BY l.id_account;', Line::TABLE, Transaction::TABLE, $year_id);
		return DB::getInstance()->getAssoc($sql);
	}

	static protected function getWhereClause(array $criterias): string
	{
		$where = [];

		if (!empty($criterias['year'])) {
			$where[] = sprintf('t.id_year = %d', $criterias['year']);
		}

		if (!empty($criterias['position'])) {
			$where[] = sprintf('a.position = %d', $criterias['position']);
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
