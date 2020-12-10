<?php

namespace Garradin\Services;

use Garradin\Config;
use Garradin\DB;
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

	static public function listGroupedWithFees(?int $user_id = null)
	{
		$services = DB::getInstance()->getGrouped('SELECT
			id, label, duration, start_date, end_date, description,
			CASE WHEN end_date IS NOT NULL THEN end_date WHEN duration IS NOT NULL THEN date(\'now\', \'+\'||duration||\' days\') ELSE NULL END AS expiry_date
			FROM services ORDER BY label COLLATE NOCASE;');
		$fees = Fees::listAllByService($user_id);
		$out = [];

		foreach ($services as $service) {
			$out[$service->id] = $service;
			$out[$service->id]->fees = [];
		}

		foreach ($fees as $fee) {
			$out[$fee->id_service]->fees[] = $fee;
		}

		return $out;
	}

	static public function listWithStats()
	{
		$db = DB::getInstance();
		return $db->get('SELECT s.*,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_service = s.id AND (expiry_date IS NULL OR expiry_date >= date()) AND paid = 1) AS nb_users_ok,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_service = s.id AND expiry_date < date()) AS nb_users_expired,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_service = s.id AND paid = 0) AS nb_users_unpaid
			FROM services s
			ORDER BY transliterate_to_ascii(s.label) COLLATE NOCASE;');
	}
}