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
	static public function getClosingSumsWithAccounts(int $year_id): array
	{
		// Find sums, link them to accounts
		$sql = sprintf('SELECT l.id_account, a.code AS account_code, a.label AS account_name, SUM(l.credit) AS credit, SUM(l.debit) AS debit
			FROM %s l
			INNER JOIN %s t ON t.id = l.id_transaction
			INNER JOIN %s a ON a.id = l.id_account
			WHERE t.id_year = %d GROUP BY l.id_account;',
			Line::TABLE, Transaction::TABLE, Account::TABLE, $year_id);
		return DB::getInstance()->getGrouped($sql);
	}

	static public function getClosingSums(int $year_id): array
	{
		$year = Years::get($year_id);

		if (true || $year->closed) {
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

	/**
	 * Grand livre
	 */
	static public function getGeneralLedger(array $criterias): \Generator
	{
		if (!empty($criterias['year'])) {
			$year_id = (int)$criterias['year'];
			$where = sprintf('t.id_year = %d', $year_id);
		}
		else {
			throw new \LogicException('Unknown criteria');
		}

		$db = DB::getInstance();

		$sql = sprintf('SELECT t.id_year, l.id_account, l.debit, l.credit, t.id, t.date, t.reference, l.reference AS line_reference, t.label, l.label AS line_label
			FROM acc_transactions t
			INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
			INNER JOIN acc_accounts a ON a.id = l.id_account
			WHERE %s
			ORDER BY a.code COLLATE NOCASE, t.date;', $where);

		$account = null;
		$debit = $credit = 0;
		$accounts = $db->getGrouped('SELECT id, code, label FROM acc_accounts WHERE id_chart = (SELECT id_chart FROM acc_years WHERE id = ?);', $year_id);

		foreach ($db->iterate($sql) as $row) {
			if (null !== $account && $account->id != $row->id_account) {
				yield $account;
				$account = null;
			}

			if (null === $account) {
				$account = (object) [
					'code'  => $accounts[$row->id_account]->code,
					'label' => $accounts[$row->id_account]->label,
					'id'    => $row->id_account,
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

		$account->all_debit = $debit;
		$account->all_credit = $credit;

		yield $account;
	}
}
