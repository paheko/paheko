<?php

namespace Paheko\Services;

use Paheko\DB;
use Paheko\UserException;
use Paheko\Users\Categories;
use Paheko\Entities\Services\Fee;
use Paheko\Entities\Accounting\Year;
use KD2\DB\EntityManager;
use KD2\DB\DB_Exception;

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

	static public function updateYear(Year $old, Year $new): bool
	{
		$db = DB::getInstance();

		if ($new->id_chart == $old->id_chart) {
			$db->preparedQuery('UPDATE services_fees SET id_year = ? WHERE id_year = ?;', $new->id(), $old->id());
			return true;
		}
		else {
			$db->preparedQuery('UPDATE services_fees SET id_year = NULL, id_account = NULL WHERE id_year = ?;', $old->id());
			return false;
		}
	}

	/**
	 * If $user_id is specified, then it will return a column 'user_amount' containing the amount that this specific user should pay
	 */
	static public function listAllByService(?int $user_id = null)
	{
		$db = DB::getInstance();

		$sql = 'SELECT *, CASE WHEN amount THEN amount ELSE NULL END AS user_amount
			FROM services_fees ORDER BY id_service, amount IS NULL, label COLLATE U_NOCASE;';
		$result = $db->get($sql);

		if (!$user_id) {
			return $result;
		}

		foreach ($result as &$row) {
			if (!$row->formula) {
				continue;
			}

			$row = self::addUserAmountToObject($row, $user_id);

			if (!empty($row->user_amount_error)) {
				$row->user_amount = -1;
				$row->label .= sprintf(' (**FORMULE DE CALCUL INVALIDE: %s**)', $row->user_amount_error);
				$row->description .= "\n\n**MERCI DE CORRIGER LA FORMULE**";
			}
		}

		return $result;
	}

	static public function addUserAmountToObject(\stdClass $object, int $user_id): \stdClass
	{
		if (!empty($object->amount)) {
			$object->user_amount = $object->amount;
		}

		if (empty($object->formula)) {
			return $object;
		}

		try {
			$sql = sprintf('SELECT (%s) FROM users WHERE id = %d;', $object->formula, $user_id);
			$object->user_amount = DB::getInstance()->firstColumn($sql);
		}
		catch (DB_Exception $e) {
			$object->user_amount_error = $e->getMessage();
		}

		return $object;
	}

	static public function listGroupedById(): array
	{
		return EntityManager::getInstance(Fee::class)->allAssoc('SELECT * FROM services_fees ORDER BY label COLLATE U_NOCASE;', 'id');
	}

	public function listWithStats()
	{
		$db = DB::getInstance();
		$hidden_cats = array_keys(Categories::listAssoc(Categories::HIDDEN_ONLY));

		$condition = sprintf('SELECT COUNT(DISTINCT su.id_user) FROM services_users su
			INNER JOIN (SELECT id, MAX(date) FROM services_users GROUP BY id_user, id_fee) su2 ON su2.id = su.id
			INNER JOIN users u ON u.id = su.id_user WHERE su.id_fee = f.id AND u.id_category NOT IN (%s)',
			implode(',', $hidden_cats));

		$sql = sprintf('SELECT f.*,
			(%s AND (su.expiry_date IS NULL OR su.expiry_date >= date()) AND su.paid = 1) AS nb_users_ok,
			(%1$s AND su.expiry_date < date()) AS nb_users_expired,
			(%1$s AND su.paid = 0) AS nb_users_unpaid
			FROM services_fees f
			WHERE id_service = ?
			ORDER BY amount, label COLLATE U_NOCASE;', $condition);

		return $db->get($sql, $this->service_id);
	}
}