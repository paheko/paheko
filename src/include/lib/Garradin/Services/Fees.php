<?php

namespace Garradin\Services;

use Garradin\Config;
use Garradin\DB;
use Garradin\Entities\Services\Fee;
use KD2\DB\EntityManager;

class Fees
{
	protected $service_id;

	public function __construct(int $id)
	{
		$this->service_id = $id;
	}

	static public function get(int $id)
	{
		return EntityManager::findOneById(Fee::class, $id);
	}

	public function listWithStats()
	{
		$db = DB::getInstance();
		return $db->get('SELECT f.*,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_fee = f.id AND expiry_date >= date() AND paid = 1) AS nb_users_ok,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_fee = f.id AND expiry_date < date()) AS nb_users_expired,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_fee = f.id AND paid = 0) AS nb_users_unpaid
			FROM services_fees f
			WHERE id_service = ?
			ORDER BY transliterate_to_ascii(label) COLLATE NOCASE;', $this->service_id);
	}
}