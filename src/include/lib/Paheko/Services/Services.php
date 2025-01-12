<?php

namespace Paheko\Services;

use Paheko\Config;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Utils;
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

	static public function listGroupedWithFeesForSelect(bool $with_all_fees = true): array
	{
		$out = [];

		foreach (self::listGroupedWithFees(null, 2) as $service) {
			$s = [
				'label' => self::getLongLabel($service),
				'options' => [
				],
			];

			if ($with_all_fees) {
				$s['options']['s' . $service->id] = '— Tous les tarifs —';
			}

			foreach ($service->fees as $fee) {
				$key = $with_all_fees ? 'f' . $fee->id : $fee->id;
				$s['options'][$key] = $fee->label;
			}

			$out[] = $s;
		}

		return $out;
	}

	static public function getLongLabel(object $service)
	{
		if ($service->duration) {
			$duration = sprintf('%d jours', $service->duration);
		}
		elseif ($service->start_date) {
			$duration = sprintf('du %s au %s', Utils::shortDate($service->start_date), Utils::shortDate($service->end_date));
		}
		else {
			$duration = 'ponctuelle';
		}

		return sprintf('%s — %s', $service->label, $duration);
	}

	static public function count()
	{
		return DB::getInstance()->count(Service::TABLE, 1);
	}

	static public function listGroupedWithFees(?int $user_id = null, int $current = 1)
	{
		if ($current === 1) {
			$where = 'WHERE end_date IS NULL OR end_date >= date()';
		}
		elseif ($current === 2) {
			$where = '';
		}
		else {
			$where = 'WHERE end_date IS NOT NULL AND end_date < date()';
		}

		$sql = sprintf('SELECT
			id, label, duration, start_date, end_date, description,
			CASE WHEN end_date IS NOT NULL THEN end_date WHEN duration IS NOT NULL THEN date(\'now\', \'+\'||duration||\' days\') ELSE NULL END AS expiry_date
			FROM services %s ORDER BY label COLLATE U_NOCASE;', $where);

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

	static public function listWithStats(bool $current_only = true): DynamicList
	{
		$db = DB::getInstance();
		$hidden_cats = array_keys(Categories::listAssoc(Categories::HIDDEN_ONLY));

		$sql = sprintf('DROP TABLE IF EXISTS services_list_stats;
			CREATE TEMP TABLE IF NOT EXISTS services_list_stats (id_service, id_user, ok, expired, paid);
			INSERT INTO services_list_stats SELECT
				id_service, id_user,
				CASE WHEN (su.expiry_date IS NULL OR su.expiry_date >= date()) AND su.paid = 1 THEN 1 ELSE 0 END,
				CASE WHEN su.expiry_date < date() THEN 1 ELSE 0 END,
				paid
			FROM services_users su
			INNER JOIN (SELECT id, MAX(date) FROM services_users GROUP BY id_user, id_service) su2 ON su2.id = su.id
			INNER JOIN users u ON u.id = su.id_user WHERE u.%s',
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

		$current_condition = $current_only ? '(end_date IS NULL OR end_date >= datetime())' : '(end_date IS NOT NULL AND end_date < datetime())';

		$list = new DynamicList($columns, 'services', $current_condition);
		$list->setPageSize(null);
		$list->orderBy('label', false);
		return $list;
	}

	static public function countOldServices(): int
	{
		return DB::getInstance()->count(Service::TABLE, 'end_date IS NOT NULL AND end_date < datetime()');
	}
}