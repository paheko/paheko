<?php

namespace Paheko\Services;

use Paheko\Config;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Users\Categories;
use Paheko\Entities\Services\Service;
use KD2\DB\EntityManager;

class Services
{
	static public function get(int $id)
	{
		return EntityManager::findOneById(Service::class, $id);
	}

	static public function listAssoc()
	{
		return DB::getInstance()->getAssoc('SELECT id, label FROM services ORDER BY label COLLATE U_NOCASE;');
	}

	static public function listAssocWithFees()
	{
		$out = [];

		foreach (self::listGroupedWithFees(null, 2) as $service) {
			$out[$service->label] = [
				's' . $service->id => '— Tous les tarifs —',
			];

			foreach ($service->fees as $fee) {
				$out[$service->label]['f' . $fee->id] = $fee->label;
			}
		}

		return $out;
	}

	static public function count()
	{
		return DB::getInstance()->count(Service::TABLE, 1);
	}

	static public function listGroupedWithFees(?int $user_id = null)
	{
		$sql = 'SELECT
			id, label, duration, start_date, end_date, description,
			CASE WHEN end_date IS NOT NULL THEN end_date WHEN duration IS NOT NULL THEN date(\'now\', \'+\'||duration||\' days\') ELSE NULL END AS expiry_date
			FROM services
			WHERE archived = 0 ORDER BY label COLLATE U_NOCASE;';

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

	static public function listArchivedWithStats(): DynamicList
	{
		$list = self::listWithStats();
		$list->setConditions('archived = 1');
		return $list;
	}

	static public function listWithStats(): DynamicList
	{
		$db = DB::getInstance();
		$hidden_cats = array_keys(Categories::listAssoc(Categories::HIDDEN_ONLY));

		$sql = sprintf('DROP TABLE IF EXISTS services_list_stats;
			CREATE TEMP TABLE IF NOT EXISTS services_list_stats (id_service, id_user, ok, expired, paid);
			INSERT INTO services_list_stats SELECT
				id_service, id_user,
				CASE WHEN (sub.expiry_date IS NULL OR sub.expiry_date >= date()) AND sub.paid = 1 THEN 1 ELSE 0 END,
				CASE WHEN sub.expiry_date < date() THEN 1 ELSE 0 END,
				paid
			FROM services_subscriptions sub
			INNER JOIN (SELECT id, MAX(date) FROM services_subscriptions GROUP BY id_user, id_service) sub2 ON sub2.id = sub.id
			INNER JOIN users u ON u.id = sub.id_user WHERE u.%s',
			$db->where('id_category', 'NOT IN', $hidden_cats));

		$db->exec($sql);


		$columns = [
			'id' => [],
			'duration' => [],
			'start_date' => [],
			'end_date' => [],
			'label' => [
				'label' => 'Activité',
			],
			'date' => [
				'label' => 'Période',
				'order' => 'start_date %s, duration %1$s',
				'select' => 'CASE WHEN start_date IS NULL THEN duration ELSE NULL END',
			],
			'nb_users_ok' => [
				'label' => 'Membres à jour',
				'order' => null,
				'select' => '(SELECT COUNT(DISTINCT id_user) FROM services_list_stats WHERE id_service = services.id AND ok = 1)',
			],
			'nb_users_expired' => [
				'label' => 'Membres expirés',
				'order' => null,
				'select' => '(SELECT COUNT(DISTINCT id_user) FROM services_list_stats WHERE id_service = services.id AND expired = 1)',
			],
			'nb_users_unpaid' => [
				'label' => 'Membres en attente de règlement',
				'order' => null,
				'select' => '(SELECT COUNT(DISTINCT id_user) FROM services_list_stats WHERE id_service = services.id AND paid = 0)',
			],
		];

		$list = new DynamicList($columns, 'services', 'archived = 0');
		$list->setPageSize(null);
		$list->orderBy('label', false);
		return $list;
	}

	static public function hasArchivedServices(): bool
	{
		return DB::getInstance()->test(Service::TABLE, 'archived = 1');
	}
}