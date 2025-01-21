<?php

namespace Paheko\Accounting;

use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Line;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Accounting\Year;
use Paheko\Utils;
use Paheko\DB;
use KD2\DB\EntityManager;
use KD2\DB\Date;

class Years
{
	static public function get(int $year_id)
	{
		return EntityManager::findOneById(Year::class, $year_id);
	}

	static public function getFirstYear(): ?Year
	{
		return EntityManager::findOne(Year::class, 'SELECT * FROM @TABLE
			ORDER BY status = ? DESC, status = ? DESC, start_date <= date() AND end_date >= date() DESC, start_date DESC LIMIT 1;',
			Year::OPEN, Year::LOCKED);
	}

	static public function getCurrentOpenYear()
	{
		return EntityManager::findOne(Year::class, 'SELECT * FROM @TABLE WHERE status = ?
			ORDER BY start_date <= date() AND end_date >= date() DESC, start_date DESC LIMIT 1;', Year::OPEN);
	}

	static public function getCurrentOpenYearId()
	{
		return EntityManager::getInstance(Year::class)->col('SELECT id FROM @TABLE WHERE status = ?
			ORDER BY start_date <= date() AND end_date >= date() DESC, start_date DESC LIMIT 1;', Year::OPEN);
	}

	static public function getMatchingOpenYearId(?\DateTimeInterface $date = null)
	{
		if (null === $date) {
			return self::getCurrentOpenYearId();
		}

		return EntityManager::getInstance(Year::class)->col('SELECT id FROM @TABLE
			WHERE status = ? AND start_date <= ? AND end_date >= ?
			ORDER BY start_date LIMIT 1;',
			Year::OPEN, $date, $date);
	}

	static public function listOpen($with_stats = false)
	{
		$db = EntityManager::getInstance(Year::class)->DB();
		$stats = $with_stats ? ', (SELECT COUNT(*) FROM acc_transactions WHERE id_year = acc_years.id) AS nb_transactions' : '';
		return $db->getGrouped(sprintf('SELECT id, * %s FROM acc_years WHERE status = %d ORDER BY end_date;', $stats, Year::OPEN));
	}

	static public function listOpenAssocExcept(int $id)
	{
		$db = EntityManager::getInstance(Year::class)->DB();
		return $db->getAssoc('SELECT id, label FROM acc_years WHERE status = ? AND id != ? ORDER BY end_date;', Year::OPEN, $id);
	}

	static public function listOpenAssoc()
	{
		$db = EntityManager::getInstance(Year::class)->DB();
		return $db->getAssoc('SELECT id, label FROM acc_years WHERE status = ? ORDER BY end_date DESC;', Year::OPEN);
	}

	static public function listAssoc()
	{
		return DB::getInstance()->getAssoc('SELECT id, label FROM acc_years ORDER BY end_date;');
	}

	static public function listAssocExcept(int $id)
	{
		return DB::getInstance()->getAssoc('SELECT id, label FROM acc_years WHERE id != ? ORDER BY end_date;', $id);
	}

	static public function listClosedAssoc()
	{
		$list = self::listClosed();
		$out = [];

		foreach ($list as $year) {
			$out[$year->id()] = $year->getLabelWithYearsAndStatus();
		}

		return $out;
	}

	static public function listClosedAssocExcept(int $id)
	{
		return DB::getInstance()->getAssoc('SELECT id, label FROM acc_years WHERE status = ? AND id != ? ORDER BY end_date DESC;', Year::CLOSED, $id);
	}

	static public function listClosed(): array
	{
		$em = EntityManager::getInstance(Year::class);
		return $em->all('SELECT * FROM @TABLE WHERE status = ? ORDER BY end_date;', Year::CLOSED);
	}

	static public function countClosed(): int
	{
		return DB::getInstance()->count(Year::TABLE, 'status = ' . Year::CLOSED);
	}

	static public function count(): int
	{
		return DB::getInstance()->count(Year::TABLE);
	}

	static public function list(): array
	{
		$em = EntityManager::getInstance(Year::class);
		return $em->all('SELECT * FROM @TABLE ORDER BY end_date DESC;');
	}

	static public function listExcept(int $id): array
	{
		$em = EntityManager::getInstance(Year::class);
		return $em->all('SELECT * FROM @TABLE WHERE id != ? ORDER BY end_date DESC;', $id);
	}

	static public function listWithStats(): \Generator
	{
		$sql = 'SELECT y.*,
			(SELECT COUNT(*) FROM acc_transactions WHERE id_year = y.id) AS nb_transactions,
			c.label AS chart_name
			FROM acc_years y
			INNER JOIN acc_charts c ON c.id = y.id_chart
			ORDER BY end_date DESC;';

		foreach (DB::getInstance()->iterate($sql) as $row) {
			$row->status_tag_preset = Year::STATUS_TAG_PRESETS[$row->status];
			yield $row;
		}
	}

	static public function listLastTransactions(int $count, array $years): array
	{
		$out = [];

		foreach ($years as $year) {
			$out[$year->id] = Transactions::listByType($year->id, null);
			$out[$year->id]->setPageSize($count);
			$out[$year->id]->orderBy('id', true);
		}

		return $out;
	}

	static public function create(?int $id_chart = null): Year
	{
		$year = new Year;

		$new_dates = Years::getNewYearDates();
		$year->start_date = $new_dates[0];
		$year->end_date = $new_dates[1];
		$year->label = sprintf('Exercice %s', $year->label_years());

		if ($id_chart) {
			$year->id_chart = $id_chart;
		}

		return $year;
	}

	static public function getNewYearDates(): array
	{
		$last_year = EntityManager::findOne(Year::class, 'SELECT * FROM @TABLE ORDER BY end_date DESC LIMIT 1;');

		if ($last_year) {
			$start_date = clone $last_year->start_date;
			$start_date->modify('+1 year');

			$end_date = clone $last_year->end_date;
			$end_date->modify('+1 year');
		}
		else {
			$start_date = new Date('January 1st');
			$end_date = new Date('December 31');
		}

		return [$start_date, $end_date];
	}

	/**
	 * Crée une écriture d'affectation automatique
	 * @param  Year   $year
	 * @return Transaction|null
	 */
	static public function makeAppropriation(Year $year): ?Transaction
	{
		$db = DB::getInstance();
		$balances = $db->getGrouped('SELECT a.type, a.id, SUM(l.credit) - SUM(l.debit) AS balance
			FROM acc_accounts a
			INNER JOIN acc_transactions_lines l ON l.id_account = a.id
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE t.id_year = ? AND (a.type = ? OR a.type = ?) GROUP BY a.type;',
			$year->id, Account::TYPE_NEGATIVE_RESULT, Account::TYPE_POSITIVE_RESULT
		);

		if (!count($balances)) {
			return null;
		}

		$appropriation_account = $db->firstColumn('SELECT id FROM acc_accounts WHERE type = ? AND id_chart = ?;',
			Account::TYPE_APPROPRIATION_RESULT, $year->id_chart);

		if (!$appropriation_account) {
			return null;
		}

		$t = new Transaction;
		$t->type = $t::TYPE_ADVANCED;
		$t->id_year = $year->id();
		$t->label = 'Affectation automatique du résultat';
		$t->notes = 'Le résultat a été affecté automatiquement lors de la balance d\'ouverture';
		$t->date = $year->start_date;
		$t->addStatus($t::STATUS_OPENING_BALANCE);

		$sum = 0;

		if (!empty($balances[Account::TYPE_NEGATIVE_RESULT])) {
			$account = $balances[Account::TYPE_NEGATIVE_RESULT];

			$line = Line::create($account->id, abs($account->balance), 0);
			$t->addLine($line);

			$sum += abs($account->balance);
		}

		if (!empty($balances[Account::TYPE_POSITIVE_RESULT])) {
			$account = $balances[Account::TYPE_POSITIVE_RESULT];

			$line = Line::create($account->id, 0, abs($account->balance));
			$t->addLine($line);

			$sum -= abs($account->balance);
		}

		if ($sum === 0) {
			return null;
		}
		elseif ($sum > 0) {
			$line = Line::create($appropriation_account, 0, $sum);
		}
		else {
			$line = Line::create($appropriation_account, abs($sum), 0);
		}

		$t->addLine($line);

		return $t;
	}
}
