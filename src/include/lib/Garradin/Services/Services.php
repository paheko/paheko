<?php

namespace Garradin\Services;

use Garradin\Config;
use Garradin\DB;
use Garradin\Membres\Categories;
use Garradin\Entities\Services\Service;
use KD2\DB\EntityManager;

class Services
{
	static public function get(int $id)
	{
		return EntityManager::findOneById(Service::class, $id);
	}

	static public function listAssoc()
	{
		return DB::getInstance()->getAssoc('SELECT id, label FROM services ORDER BY label COLLATE NOCASE;');
	}

	static public function count()
	{
		return DB::getInstance()->count(Service::TABLE, 1);
	}

	static public function listGroupedWithFees(?int $user_id = null, bool $current_only = true)
	{
		$where = $current_only ? 'WHERE end_date IS NULL OR end_date >= date()' : 'WHERE end_date IS NOT NULL AND end_date < date()';

		$sql = sprintf('SELECT
			id, label, duration, start_date, end_date, description,
			CASE WHEN end_date IS NOT NULL THEN end_date WHEN duration IS NOT NULL THEN date(\'now\', \'+\'||duration||\' days\') ELSE NULL END AS expiry_date
			FROM services %s ORDER BY label COLLATE NOCASE;', $where);

		$services = DB::getInstance()->getGrouped($sql);
		$fees = Fees::listAllByService($user_id);
		$out = [];

		foreach ($services as $service) {
			$out[$service->id] = $service;
			$out[$service->id]->fees = [];
		}

		foreach ($fees as $fee) {
			if (isset($out[$fee->id_service])) {
				$out[$fee->id_service]->fees[] = $fee;
			}
		}

		return $out;
	}

	static public function listWithStats()
	{
		$db = DB::getInstance();
		$hidden_cats = array_keys((new Categories)->listHidden());

		$condition = sprintf('SELECT COUNT(DISTINCT su.id_user) FROM services_users su
			INNER JOIN (SELECT id, MAX(date) FROM services_users GROUP BY id_user, id_service) su2 ON su2.id = su.id
			INNER JOIN membres m ON m.id = su.id_user WHERE su.id_service = s.id AND m.id_categorie NOT IN (%s)',
			implode(',', $hidden_cats));

		$sql = sprintf('SELECT s.*,
			(%s AND (expiry_date IS NULL OR expiry_date >= date()) AND paid = 1) AS nb_users_ok,
			(%1$s AND expiry_date < date()) AS nb_users_expired,
			(%1$s AND paid = 0) AS nb_users_unpaid
			FROM services s
			ORDER BY transliterate_to_ascii(s.label) COLLATE NOCASE;', $condition);

		return $db->get($sql);
	}
}