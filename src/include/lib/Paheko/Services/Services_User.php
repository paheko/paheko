<?php

namespace Paheko\Services;

use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Utils;
use Paheko\Entities\Services\Service_User;
use KD2\DB\EntityManager;

class Services_User
{
	static public function get(int $id)
	{
		return EntityManager::findOneById(Service_User::class, $id);
	}

	static public function countForUser(int $user_id)
	{
		return DB::getInstance()->count(Service_User::TABLE, 'id_user = ?', $user_id);
	}

	static public function listDistinctForUser(int $user_id)
	{
		return DB::getInstance()->get('SELECT
			s.label, MAX(su.date) AS last_date, su.expiry_date AS expiry_date, sf.label AS fee_label, su.paid, s.end_date,
			CASE WHEN su.expiry_date < date() THEN -1 WHEN su.expiry_date >= date() THEN 1 ELSE 0 END AS status,
			CASE WHEN s.end_date < date() THEN 1 ELSE 0 END AS archived
			FROM services_users su
			INNER JOIN services s ON s.id = su.id_service
			INNER JOIN services_fees sf ON sf.id = su.id_fee
			WHERE su.id_user = ?
			GROUP BY su.id_service ORDER BY expiry_date DESC;', $user_id);
	}

	static public function perUserList(int $user_id, ?int $only_id = null, ?\DateTime $after = null): DynamicList
	{
		$columns = [
			'id' => [
				'select' => 'su.id',
			],
			'id_account' => [
				'select' => 'sf.id_account',
			],
			'id_year' => [
				'select' => 'sf.id_year',
			],
			'account_code' => [
				'select' => 'a.code',
			],
			'has_transactions' => [
				'select' => 'tu.id_user',
			],
			'label' => [
				'select' => 's.label',
				'label' => 'Activité',
			],
			'fee' => [
				'label' => 'Tarif',
				'select' => 'sf.label',
			],
			'date' => [
				'label' => 'Date d\'inscription',
				'select' => 'su.date',
			],
			'expiry' => [
				'label' => 'Date d\'expiration',
				'select' => 'MAX(su.expiry_date)',
			],
			'paid' => [
				'label' => 'Payé',
				'select' => 'su.paid',
			],
			'amount' => [
				'label' => 'Reste à régler',
				'select' => 'CASE WHEN su.paid = 1 AND COUNT(tl.debit) = 0 THEN NULL
					ELSE MAX(0, expected_amount - IFNULL(SUM(tl.debit), 0)) END',
			],
			'expected_amount' => [],
		];

		$tables = 'services_users su
			INNER JOIN services s ON s.id = su.id_service
			LEFT JOIN services_fees sf ON sf.id = su.id_fee
			LEFT JOIN acc_accounts a ON sf.id_account = a.id
			LEFT JOIN acc_transactions_users tu ON tu.id_service_user = su.id
			LEFT JOIN acc_transactions_lines tl ON tl.id_transaction = tu.id_transaction';
		$conditions = sprintf('su.id_user = %d', $user_id);

		if ($only_id) {
			$conditions .= sprintf(' AND su.id = %d', $only_id);
		}

		if ($after) {
			$conditions .= sprintf(' AND su.date >= %s', DB::getInstance()->quote($after->format('Y-m-d')));
		}

		$list = new DynamicList($columns, $tables, $conditions);

		$list->setExportCallback(function (&$row) {
			$row->amount = $row->amount ? Utils::money_format($row->amount, '.', '', false) : null;
		});

		$list->orderBy('date', true);
		$list->groupBy('su.id');
		$list->setCount('COUNT(DISTINCT su.id)');
		return $list;
	}
}