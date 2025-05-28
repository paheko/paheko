<?php

namespace Paheko\Services;

use Paheko\CSV_Custom;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\Entities\Services\Subscription;
use Paheko\Users\DynamicFields;
use Paheko\Users\Users;

use KD2\DB\EntityManager;
use KD2\DB\Date;

class Subscriptions
{
	static public function get(int $id)
	{
		return EntityManager::findOneById(Subscription::class, $id);
	}

	static public function countForUser(int $user_id)
	{
		return DB::getInstance()->count(Subscription::TABLE, 'id_user = ?', $user_id);
	}

	static public function createFromFee(int $id_fee, int $id_user, ?int $expected_amount, bool $paid, int $qty = 1): Service_User
	{
		$su = new Service_User;
		$su->date = new Date;
		// Required, also to calculate expiry date
		$id_service = DB::getInstance()->firstColumn('SELECT id_service FROM services_fees WHERE id = ?;', $id_fee);
		$su->importForm(compact('id_service', 'id_fee', 'id_user', 'paid', 'expected_amount', 'qty'));
		return $su;
	}

	static public function listDistinctForUser(int $user_id)
	{
		return DB::getInstance()->get('SELECT
			s.label, MAX(sub.date) AS last_date, sub.expiry_date AS expiry_date, sf.label AS fee_label, sub.paid, s.end_date,
			CASE WHEN sub.expiry_date < date() THEN -1 WHEN sub.expiry_date >= date() THEN 1 ELSE 0 END AS status,
			CASE WHEN s.end_date < date() THEN 1 ELSE 0 END AS archived
			FROM services_subscriptions sub
			INNER JOIN services s ON s.id = sub.id_service
			LEFT JOIN services_fees sf ON sf.id = sub.id_fee
			WHERE sub.id_user = ?
			AND s.archived = 0
			GROUP BY sub.id_service ORDER BY expiry_date DESC;', $user_id);
	}

	static public function perUserList(int $user_id, ?int $only_id = null, ?\DateTime $after = null): DynamicList
	{
		$columns = [
			'archived' => [
				'select' => 's.archived',
			],
			'id' => [
				'select' => 'sub.id',
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
				'select' => 'tu.id_transaction',
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
				'select' => 'sub.date',
			],
			'expiry' => [
				'label' => 'Date d\'expiration',
				'select' => 'MAX(sub.expiry_date)',
			],
			'paid' => [
				'label' => 'Payé',
				'select' => 'sub.paid',
			],
			'amount' => [
				'label' => 'Reste à régler',
				'select' => 'CASE WHEN sub.paid = 1 AND COUNT(tl.debit) = 0 THEN NULL
					ELSE MAX(0, expected_amount - IFNULL(SUM(tl.debit), 0)) END',
			],
			'expected_amount' => [],
		];

		$tables = 'services_subscriptions sub
			INNER JOIN services s ON s.id = sub.id_service
			LEFT JOIN services_fees sf ON sf.id = sub.id_fee
			LEFT JOIN acc_accounts a ON sf.id_account = a.id
			LEFT JOIN acc_transactions_users tu ON tu.id_subscription = sub.id
			LEFT JOIN acc_transactions_lines tl ON tl.id_transaction = tu.id_transaction';
		$conditions = sprintf('sub.id_user = %d', $user_id);

		if ($only_id) {
			$conditions .= sprintf(' AND sub.id = %d', $only_id);
		}

		if ($after) {
			$conditions .= sprintf(' AND sub.date >= %s', DB::getInstance()->quote($after->format('Y-m-d')));
		}

		$list = new DynamicList($columns, $tables, $conditions);

		$list->setExportCallback(function (&$row) {
			$row->amount = $row->amount ? Utils::money_format($row->amount, '.', '', false) : null;
		});

		$list->orderBy('date', true);
		$list->groupBy('sub.id');
		$list->setCount('COUNT(DISTINCT sub.id)');
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

				if (!empty($row->id)) {
					$su = self::get((int)$row->id);

					if (!$su) {
						throw new UserException(sprintf('L\'inscription numéro %d n\'existe pas', $row->id));
					}
				}
				else {
					$su = self::create($id_user, $id_service, $id_fee);
				}

				unset($row->fee, $row->service, $row->$number_field, $row->id_service, $row->id_fee, $row->id);

				if (empty($row->paid) || strtolower(trim($row->paid)) === 'non') {
					$row->paid = false;
				}
				else {
					$row->paid = true;
				}

				if (!empty($row->expected_amount)) {
					$row->expected_amount = Utils::moneyToInteger($row->expected_amount);
				}

				$su->importForm((array)$row);

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

	static public function create(int $id_user, int $id_service, ?int $id_fee): Subscription
	{
		$su = new Subscription;
		$su->set('id_user', $id_user);
		$su->set('id_service', $id_service);
		$su->set('id_fee', $id_fee);
		return $su;
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

	static public function getList(): DynamicList
	{
		$number_field = DynamicFields::getNumberFieldSQL('u');
		$name_field = DynamicFields::getNameFieldsSQL('u');

		$columns = [
			'number' => [
				'label' => 'Numéro de membre',
				'select' => $number_field,
				'export' => true,
			],
			'name' => [
				'label' => 'Nom du membre',
				'select' => $name_field,
			],
			'id' => [
				'label' => 'Numéro d\'inscription',
				'select' => 'sub.id',
				'export' => true,
			],
			'service' => [
				'label' => 'Activité',
				'select' => 's.label',
			],
			'fee' => [
				'label' => 'Tarif',
				'select' => 'sf.label',
			],
			'paid' => [
				'label' => 'Payé',
				'select' => 'sub.paid',
			],
			'expected_amount' => [
				'label' => 'Montant de l\'inscription',
				'select' => 'sub.expected_amount',
				'export' => true,
			],
			'paid_amount' => [
				'label' => 'Montant réglé',
				'select' => 'SUM(tl.credit)',
				'export' => true,
			],
			'left_amount' => [
				'label' => 'Reste à régler',
				'select' => 'CASE WHEN sub.paid = 1 AND COUNT(tl.debit) = 0 THEN NULL
					ELSE MAX(0, expected_amount - IFNULL(SUM(tl.debit), 0)) END',
			],
			'date' => [
				'label' => 'Date d\'inscription',
				'select' => 'sub.date',
			],
			'expiry_date' => [
				'label' => 'Date d\'expiration',
				'select' => 'sub.expiry_date',
			],
			'id_user' => ['select' => 'sub.id_user'],
			'id_fee' => ['select' => 'sub.id_fee'],
			'id_service' => ['select' => 'sub.id_service'],
		];

		$tables = 'services_subscriptions sub
			INNER JOIN services s ON s.id = sub.id_service
			INNER JOIN users u ON u.id = sub.id_user
			LEFT JOIN services_fees sf ON sf.id = sub.id_fee
			LEFT JOIN acc_transactions_users tu ON tu.id_subscription = sub.id
			LEFT JOIN acc_transactions_lines tl ON tl.id_transaction = tu.id_transaction';

		$list = new DynamicList($columns, $tables);
		$list->orderBy('id', true);
		$list->groupBy('sub.id');
		$list->setTitle('Historique des inscriptions');
		$list->setModifier(function (&$row) {
			$row->date = \DateTime::createFromFormat('!Y-m-d', $row->date);
			$row->expiry_date = \DateTime::createFromFormat('!Y-m-d', $row->expiry_date);
		});
		$list->setExportCallback(function (&$row) {
			$row->paid = $row->paid ? 'Oui' : '';
		});

		return $list;
	}

	static public function listImportColumns(): array
	{
		$number_field = DynamicFields::getNumberField();

		return [
			'id'              => 'Numéro d\'inscription',
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