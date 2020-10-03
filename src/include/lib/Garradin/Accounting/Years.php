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

	static public function getCurrentOpenYearIfSingle()
	{
		return EntityManager::findOne(Year::class, 'SELECT * FROM @TABLE WHERE closed = 0 GROUP BY closed HAVING COUNT(*) = 1;');
	}

	static public function listOpen()
	{
		$em = EntityManager::getInstance(Year::class);
		return $em->all('SELECT * FROM @TABLE WHERE closed = 0 ORDER BY end_date;');
	}

	static public function listClosed()
	{
		$em = EntityManager::getInstance(Year::class);
		return $em->all('SELECT * FROM @TABLE WHERE closed = 1 ORDER BY end_date;');
	}

	static public function list(bool $reverse = false)
	{
		$desc = $reverse ? 'DESC' : '';
		$em = EntityManager::getInstance(Year::class);
		return $em->all(sprintf('SELECT * FROM @TABLE ORDER BY end_date %s;', $desc));
	}

	static public function getNewYearDates(): array
	{
		$last_year = EntityManager::findOne(Year::class, 'SELECT * FROM @TABLE ORDER BY end_date DESC LIMIT 1;');

		if ($last_year) {
			$diff = $last_year->start_date->diff($last_year->end_date);

			$start_date = clone $last_year->end_date;
			$start_date->modify('+1 day');

			$end_date = clone $start_date;
			$end_date->add($diff);
		}
		else {
			$start_date = new \DateTime;
			$end_date = clone $start_date;
			$end_date->modify('+1 year');
		}

		return [$start_date, $end_date];
	}
}