<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Project;
use Garradin\DB;

use KD2\DB\EntityManager;

class Projects
{
	static public function get(int $id): ?Project
	{
		return EntityManager::findOneById(Project::class, $id);
	}

	static public function getIdFromCodeOrLabel(string $str): ?int
	{
		return DB::getInstance()->firstColumn('SELECT id FROM acc_projects WHERE code = ? OR label = ?;', $str, $str) ?: null;
	}

	static public function getName(?int $id): ?string
	{
		if (!$id) {
			return null;
		}

		static $projects = [];

		if (array_key_exists($id, $projects)) {
			return $projects[$id];
		}

		$p = DB::getInstance()->first('SELECT code, label FROM acc_projects WHERE id = ?;', $id);

		$projects[$id] = $p ? ($p->code ? sprintf('%s — %s', $p->code, $p->label) : $p->label) : null;
		return $projects[$id];
	}

	static public function count(): int
	{
		return DB::getInstance()->count(Project::TABLE);
	}

	static public function listAssoc(): array
	{
		$em = EntityManager::getInstance(Project::class);
		$sql = $em->formatQuery('SELECT id, CASE WHEN code IS NOT NULL THEN code || \' — \' || label ELSE label END FROM @TABLE WHERE archived = 0 ORDER BY code COLLATE NOCASE, label COLLATE U_NOCASE;');
		return $em->DB()->getAssoc($sql);
	}

	static public function listAssocWithEmpty(): array
	{
		return ['' => '-- Aucun'] + self::listAssoc();
	}

	/**
	 * Return account balances per year or per project
	 * @param  bool $by_year If true will return projects grouped by year, if false it will return years grouped by project
	 */
	static public function getBalances(bool $by_year = false): \Generator
	{
		$join = $by_year ? 'INNER' : 'LEFT';
		$sql = 'SELECT p.label AS project_label, p.description AS project_description, p.id AS id_project,
			p.code AS project_code, p.archived, p.id AS project_id,
			y.id AS id_year, y.label AS year_label, y.start_date, y.end_date,
			SUM(l.credit - l.debit) AS sum, SUM(l.credit) AS credit, SUM(l.debit) AS debit, 0 AS total,
			(SELECT SUM(l2.credit - l2.debit) FROM acc_transactions_lines l2
				INNER JOIN acc_transactions t2 ON t2.id = l2.id_transaction
				INNER JOIN acc_accounts a2 ON a2.id = l2.id_account
				WHERE a2.position = %d AND l2.id_project = l.id_project AND t2.id_year = t.id_year) * -1 AS sum_expense,
			(SELECT SUM(l2.credit - l2.debit) FROM acc_transactions_lines l2
				INNER JOIN acc_transactions t2 ON t2.id = l2.id_transaction
				INNER JOIN acc_accounts a2 ON a2.id = l2.id_account
				WHERE a2.position = %d AND l2.id_project = l.id_project AND t2.id_year = t.id_year) AS sum_revenue
			FROM acc_projects p
			%s JOIN acc_transactions_lines l ON p.id = l.id_project
			%3$s JOIN acc_transactions t ON t.id = l.id_transaction
			%3$s JOIN acc_years y ON y.id = t.id_year
			GROUP BY %s
			ORDER BY p.archived, %s;';

		$order = 'p.code COLLATE NOCASE, p.label COLLATE U_NOCASE';

		if ($by_year) {
			$group = 'y.id, p.id';
			$order = 'y.start_date DESC, ' . $order;
		}
		else {
			$group = 'p.id, y.id';
			$order = $order . ', y.id';
		}

		$sql = sprintf($sql, Account::EXPENSE, Account::REVENUE, $join, $group, $order);

		$current = null;

		static $sums = ['credit', 'debit', 'sum', 'sum_expense', 'sum_revenue'];

		$total = function (\stdClass $current, bool $by_year) use ($sums)
		{
			$out = (object) [
				'label' => 'Total',
				'id_project' => $by_year ? null : $current->id,
				'id_year' => $by_year ? $current->id : null,
				'total' => 1,
			];

			foreach ($sums as $s) {
				$out->{$s} = $current->{$s};
			}

			return $out;
		};

		foreach (DB::getInstance()->iterate($sql) as $row) {
			$id = $by_year ? $row->id_year : $row->project_id;

			if (null !== $current && $current->selector !== $id) {
				if (count($current->items)) {
					$current->items[] = $total($current, $by_year);
				}

				yield $current;
				$current = null;
			}

			if (null === $current) {
				$current = (object) [
					'selector' => $id,
					'id' => $by_year ? $row->id_year : $row->id_project,
					'label' => $by_year ? $row->year_label : ($row->project_code  ? $row->project_code . ' — ' : '') . $row->project_label,
					'id_year' => $by_year ? $row->id_year : null,
					'description' => !$by_year ? $row->project_description : null,
					'archived' => !$by_year ? $row->archived : 0,
					'items' => [],
				];

				foreach ($sums as $s) {
					$current->$s = 0;
				}
			}

			if (null === $row->sum) {
				continue;
			}

			$row->label = !$by_year ? $row->year_label : ($row->project_code  ? $row->project_code . ' — ' : '') . $row->project_label;
			$current->items[] = $row;

			foreach ($sums as $s) {
				$current->$s += $row->$s;
			}
		}

		if ($current === null) {
			return;
		}

		if (count($current->items)) {
			$current->items[] = $total($current, $by_year);
		}

		yield $current;
	}

}
