<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Year;
use Garradin\Utils;
use Garradin\DB;
use KD2\DB\EntityManager;

class Years
{
	static public function get(int $year_id)
	{
		return EntityManager::findOneById(Year::class, $year_id);
	}

	static public function getCurrentOpenYear()
	{
		return EntityManager::findOne(Year::class, 'SELECT * FROM @TABLE WHERE closed = 0 ORDER BY start_date LIMIT 1;');
	}

	static public function getCurrentOpenYearId()
	{
		return EntityManager::getInstance(Year::class)->col('SELECT id FROM @TABLE WHERE closed = 0 ORDER BY start_date LIMIT 1;');
	}

	static public function listOpen()
	{
		$db = EntityManager::getInstance(Year::class)->DB();
		return $db->get('SELECT *, (SELECT COUNT(*) FROM acc_transactions WHERE id_year = acc_years.id) AS nb_transactions
			FROM acc_years WHERE closed = 0 ORDER BY end_date;');
	}

	static public function listOpenAssocExcept(int $id)
	{
		$db = EntityManager::getInstance(Year::class)->DB();
		return $db->getAssoc('SELECT id, label FROM acc_years WHERE closed = 0 AND id != ? ORDER BY end_date;', $id);
	}

	static public function listAssoc()
	{
		return DB::getInstance()->getAssoc('SELECT id, label FROM acc_years ORDER BY end_date;');
	}

	static public function listClosed()
	{
		$em = EntityManager::getInstance(Year::class);
		return $em->all('SELECT * FROM @TABLE WHERE closed = 1 ORDER BY end_date;');
	}

	static public function countClosed()
	{
		return DB::getInstance()->count(Year::TABLE, 'closed = 1');
	}

	static public function list(bool $reverse = false)
	{
		$desc = $reverse ? 'DESC' : '';
		return DB::getInstance()->get(sprintf('SELECT y.*,
			(SELECT COUNT(*) FROM acc_transactions WHERE id_year = y.id) AS nb_transactions,
			c.label AS chart_name
			FROM acc_years y
			INNER JOIN acc_charts c ON c.id = y.id_chart
			ORDER BY end_date %s;', $desc));
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
			$start_date = new \DateTime;
			$end_date = clone $start_date;
			$end_date->modify('+1 year');
		}

		return [$start_date, $end_date];
	}
}