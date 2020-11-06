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
		$services = self::listAssoc();
		$fees = Fees::listAllByService($user_id);
		$out = [];

		foreach ($fees as $fee) {
			$id = $fee->id_service;

			if (!array_key_exists($id, $out)) {
				$out[$id] = (object) ['label' => $services[$id], 'id' => $id, 'fees' => []];
			}

			$out[$id]->fees[] = $fee;
		}

		return $out;
	}

	static public function listWithStats()
	{
		$db = DB::getInstance();
		return $db->get('SELECT s.*,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_service = s.id AND expiry_date >= date() AND paid = 1) AS nb_users_ok,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_service = s.id AND expiry_date < date()) AS nb_users_expired,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_service = s.id AND paid = 0) AS nb_users_unpaid
			FROM services s
			ORDER BY transliterate_to_ascii(s.label) COLLATE NOCASE;');
	}
}