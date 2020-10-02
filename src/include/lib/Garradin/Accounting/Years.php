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

	static public function list()
	{
		$em = EntityManager::getInstance(Year::class);
		return $em->all('SELECT * FROM @TABLE ORDER BY end_date;');
	}
}