<?php

namespace Paheko\Services;

use Paheko\CSV_Custom;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\Entities\Services\Service_User;
use Paheko\Users\DynamicFields;
use Paheko\Users\Users;

use KD2\DB\EntityManager;
use KD2\DB\Date;

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

	static public function createFromFee(int $id_fee, int $id_user, ?int $expected_amount, bool $paid): Service_User
	{
		$su = new Service_User;
		$su->date = new Date;
		// Required, also to calculate expiry date
		$id_service = DB::getInstance()->firstColumn('SELECT id_service FROM services_fees WHERE id = ?;', $id_fee);
		$su->importForm(compact('id_service', 'id_fee', 'id_user', 'paid', 'expected_amount'));
		return $su;
	}

	static public function listDistinctForUser(int $user_id)
	{
		return DB::getInstance()->get('SELECT
			s.label, MAX(su.date) AS last_date, su.expiry_date AS expiry_date, sf.label AS fee_label, su.paid, s.end_date,
			CASE WHEN su.expiry_date < date() THEN -1 WHEN su.expiry_date >= date() THEN 1 ELSE 0 END AS status,
			CASE WHEN s.end_date < date() THEN 1 ELSE 0 END AS archived
			FROM services_users su
			INNER JOIN services s ON s.id = su.id_service
			LEFT JOIN services_fees sf ON sf.id = su.id_fee
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

	static protected function iterateImport(CSV_Custom $csv, ?array &$errors = null): \Generator
	{
		$number_field = DynamicFields::getNumberField();
		$services = Services::listAssoc();
		$fees = Fees::listGroupedById();

		foreach ($csv->iterate() as $i => $row) {
			try {
				if (empty($row->$number_field)) {
					throw new UserException('Aucun numéro de membre n\'a été indiqué');
				}

				$id_user = Users::getIdFromNumber($row->$number_field);

				if (!$id_user) {
					throw new UserException(sprintf('Le numéro de membre "%s" n\'existe pas', $row->$number_field));
				}

				$id_service = array_search($row->service, $services);

				if (!$id_service) {
					throw new UserException(sprintf('L\'activité "%s" n\'existe pas', $row->service));
				}

				if (empty($row->date)) {
					throw new UserException('La date est vide');
				}

				$id_fee = null;

				if (!empty($row->fee)) {
					foreach ($fees as $fee) {
						if (strcasecmp($fee->label, $row->fee) === 0 && $fee->id_service === $id_service) {
							$id_fee = $fee->id;
							break;
						}
					}

					if (!$id_fee) {
						throw new UserException(sprintf('Le tarif "%s" n\'existe pas pour cette activité', $row->fee));
					}
				}

				$su = new Service_User;
				$su->set('id_user', $id_user);
				$su->set('id_service', $id_service);
				$su->set('id_fee', $id_fee);
				unset($row->fee, $row->service, $row->$number_field);

				if (empty($row->paid) || strtolower(trim($row->paid)) === 'non') {
					$row->paid = false;
				}
				else {
					$row->paid = true;
				}

				$su->import((array)$row);

				yield $i => $su;
			}
			catch (UserException $e) {
				if (null !== $errors) {
					$errors[] = sprintf('Ligne %d : %s', $i, $e->getMessage());
					continue;
				}

				throw $e;
			}
		}
	}

	static public function import(CSV_Custom $csv): void
	{
		$db = DB::getInstance();
		$db->begin();

		foreach (self::iterateImport($csv) as $i => $su) {
			try {
				$su->save();
			}
			catch (UserException $e) {
				throw new UserException(sprintf('Ligne %d : %s', $i, $e->getMessage()), 0, $e);
			}
		}

		$db->commit();
	}

	static public function listImportColumns(): array
	{
		$number_field = DynamicFields::getNumberField();

		return [
			$number_field     => 'Numéro de membre',
			'service'         => 'Activité',
			'fee'             => 'Tarif',
			'paid'            => 'Payé ?',
			'expected_amount' => 'Montant à régler',
			'date'            => 'Date d\'inscription',
			'expiry_date'     => 'Date d\'expiration',
		];
	}

	static public function listMandatoryImportColumns(): array
	{
		$number_field = DynamicFields::getNumberField();

		return [
			$number_field,
			'service',
			'date',
		];
	}
}