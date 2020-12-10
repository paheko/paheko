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

	/**
	 * If $user_id is specified, then it will return a column 'user_amount' containing the amount that this specific user should pay
	 */
	static public function listAllByService(?int $user_id = null)
	{
		$db = DB::getInstance();

		$sql = 'SELECT *, CASE WHEN amount THEN amount ELSE NULL END AS user_amount
			FROM services_fees ORDER BY id_service, label COLLATE NOCASE;';
		$result = $db->get($sql);

		if (!$user_id) {
			return $result;
		}

		foreach ($result as &$row) {
			if ($row->formula) {
				$sql = sprintf('SELECT %s FROM membres WHERE id = %d;', $row->formula, $user_id);
				$row->user_amount = $db->firstColumn($sql);
			}
		}

		usort($result, function ($a, $b) {
			if ($a->user_amount == $b->user_amount) {
				return 0;
			}

			return $a->user_amount > $b->user_amount ? 1 : -1;
		});

		return $result;
	}

	public function listWithStats()
	{
		$db = DB::getInstance();
		return $db->get('SELECT f.*,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_fee = f.id AND (expiry_date >= date() OR expiry_date IS NULL) AND paid = 1) AS nb_users_ok,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_fee = f.id AND expiry_date < date()) AS nb_users_expired,
			(SELECT COUNT(DISTINCT id_user) FROM services_users WHERE id_fee = f.id AND paid = 0) AS nb_users_unpaid
			FROM services_fees f
			WHERE id_service = ?
			ORDER BY amount, transliterate_to_ascii(label) COLLATE NOCASE;', $this->service_id);
	}
}