<?php

namespace Paheko\Services;

use Paheko\DB;
use Paheko\DynamicList;
use Paheko\UserException;
use Paheko\Users\Categories;
use Paheko\Entities\Services\Fee;
use Paheko\Entities\Accounting\Year;
use KD2\DB\EntityManager;
use KD2\DB\DB_Exception;

class Fees
{
	protected int $service_id;

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

	public function listWithStats(): DynamicList
	{
		$db = DB::getInstance();
		$hidden_cats = array_keys(Categories::listAssoc(Categories::HIDDEN_ONLY));

		$sql = sprintf('DROP TABLE IF EXISTS fees_list_stats;
			CREATE TEMP TABLE IF NOT EXISTS fees_list_stats (id_fee, id_user, ok, expired, paid);
			INSERT INTO fees_list_stats SELECT
				id_fee, id_user,
				CASE WHEN (su.expiry_date IS NULL OR su.expiry_date >= date()) AND su.paid = 1 THEN 1 ELSE 0 END,
				CASE WHEN su.expiry_date < date() THEN 1 ELSE 0 END,
				paid
			FROM services_users su
			INNER JOIN (SELECT id, MAX(date) FROM services_users GROUP BY id_user, id_fee) su2 ON su2.id = su.id
			INNER JOIN users u ON u.id = su.id_user WHERE u.%s',
			$db->where('id_category', 'NOT IN', $hidden_cats));

		$db->exec($sql);

		$columns = [
			'id' => [],
			'formula' => [],
			'label' => [
				'label' => 'Tarif',
			],
			'amount' => [
				'label' => 'Montant',
				'select' => 'CASE WHEN formula IS NOT NULL THEN -1 WHEN amount IS NOT NULL THEN amount ELSE 0 END',
			],
			'nb_users_ok' => [
				'label' => 'Membres à jour',
				'order' => null,
				'select' => '(SELECT COUNT(DISTINCT id_user) FROM fees_list_stats WHERE id_fee = fees.id AND ok = 1)',
			],
			'nb_users_expired' => [
				'label' => 'Membres expirés',
				'order' => null,
				'select' => '(SELECT COUNT(DISTINCT id_user) FROM fees_list_stats WHERE id_fee = fees.id AND expired = 1)',
			],
			'nb_users_unpaid' => [
				'label' => 'Membres en attente de règlement',
				'order' => null,
				'select' => '(SELECT COUNT(DISTINCT id_user) FROM fees_list_stats WHERE id_fee = fees.id AND paid = 0)',
			],
		];

		$list = new DynamicList($columns, 'services_fees AS fees', 'id_service = ' . $this->service_id);
		$list->setPageSize(null);
		$list->orderBy('label', false);
		return $list;
	}
}