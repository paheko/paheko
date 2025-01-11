<?php

namespace Paheko\Entities\Services;

use KD2\DB\Date;

use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Entity;
use Paheko\ValidationException;
use Paheko\Utils;
use Paheko\Users\DynamicFields;
use Paheko\Services\Fees;
use Paheko\Services\Services;

class Service extends Entity
{
	const NAME = 'Activité';
	const PRIVATE_URL = '!services/fees/?id=%d';

	const TABLE = 'services';

	protected int $id;
	protected string $label;
	protected ?string $description = null;
	protected ?int $duration = null;
	protected ?Date $start_date = null;
	protected ?Date $end_date = null;

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert(trim((string) $this->label) !== '', 'Le libellé doit être renseigné');
		$this->assert(strlen((string) $this->label) <= 200, 'Le libellé doit faire moins de 200 caractères');
		$this->assert(strlen((string) $this->description) <= 2000, 'La description doit faire moins de 2000 caractères');
		$this->assert(!isset($this->duration, $this->start_date, $this->end_date) || $this->duration || ($this->start_date && $this->end_date), 'Seulement une option doit être choisie : durée ou dates de début et de fin de validité');
		$this->assert(null === $this->start_date || $this->start_date instanceof \DateTimeInterface);
		$this->assert(null === $this->end_date || $this->end_date instanceof \DateTimeInterface);
		$this->assert(null === $this->duration || (is_int($this->duration) && $this->duration > 0), 'La durée n\'est pas valide');
		$this->assert(null === $this->start_date || $this->end_date >= $this->start_date, 'La date de fin de validité ne peut être avant la date de début');
	}

	public function importForm(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['period'])) {
			if (1 == $source['period']) {
				$source['start_date'] = $source['end_date'] = null;
			}
			elseif (2 == $source['period']) {
				$source['duration'] = null;
			}
			else {
				$source['duration'] = $source['start_date'] = $source['end_date'] = null;
			}
		}

		parent::importForm($source);
	}

	public function fees()
	{
		return new Fees($this->id());
	}

	public function allUsersList(bool $include_hidden_categories = false): DynamicList
	{
		$id_field = DynamicFields::getNameFieldsSQL('u');
		$columns = [
			'id_user' => [
			],
			'end_date' => [
			],
			'service_label' => [
				'select' => 's.label',
				'label' => 'Activité',
				'export' => true,
			],
			'user_number' => [
				'label' => 'Numéro de membre',
				'select' => 'u.' . DynamicFields::getNumberField(),
				'export' => true,
			],
			'identity' => [
				'label' => 'Membre',
				'select' => $id_field,
				'order' => '_user_name_index %s',
			],
			'status' => [
				'label' => 'Statut',
				'select' => 'CASE WHEN su.expiry_date < date() THEN -1 WHEN su.expiry_date >= date() THEN 1 ELSE 0 END',
			],
			'paid' => [
				'label' => 'Payé ?',
				'select' => 'su.paid',
				'order' => 'su.paid %s, su.date %1$s',
			],
			'expiry' => [
				'label' => 'Date d\'expiration',
				'select' => 'MAX(su.expiry_date)',
			],
			'fee' => [
				'label' => 'Tarif',
				'select' => 'sf.label',
			],
			'date' => [
				'label' => 'Date d\'inscription',
				'select' => 'su.date',
			],
			'_user_name_index' => [
				'select' => DynamicFields::getNameFieldsSearchableSQL('us'),
			],
		];

		$tables = 'services_users su
			INNER JOIN users u ON u.id = su.id_user
			INNER JOIN users_search us ON us.id = u.id
			INNER JOIN services s ON s.id = su.id_service
			LEFT JOIN services_fees sf ON sf.id = su.id_fee
			INNER JOIN (SELECT id, MAX(date) FROM services_users GROUP BY id_user, id_service) AS su2 ON su2.id = su.id';
		$conditions = sprintf('su.id_service = %d', $this->id());

		if (!$include_hidden_categories) {
			$conditions .= ' AND u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)';
		}

		$list = new DynamicList($columns, $tables, $conditions);
		$list->groupBy('su.id_user');
		$list->orderBy('paid', true);
		$list->setCount('COUNT(DISTINCT su.id_user)');

		$list->setExportCallback(function (&$row) {
			$row->status = $row->status == -1 ? 'En retard' : ($row->status == 1 ? 'En cours' : '');
			$row->paid = $row->paid ? 'Oui' : 'Non';
		});

		return $list;
	}

	public function activeUsersList(bool $include_hidden_categories = false): DynamicList
	{
		$list = $this->allUsersList();
		$conditions = sprintf('su.id_service = %d AND (su.expiry_date >= date() OR su.expiry_date IS NULL)
			AND su.paid = 1', $this->id());

		if (!$include_hidden_categories) {
			$conditions .= ' AND u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)';
		}

		$list->setConditions($conditions);
		return $list;
	}

	public function unpaidUsersList(bool $include_hidden_categories = false): DynamicList
	{
		$list = $this->allUsersList();
		$conditions = sprintf('su.id_service = %d AND su.paid = 0', $this->id());

		if (!$include_hidden_categories) {
			$conditions .= ' AND u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)';
		}

		$list->setConditions($conditions);
		return $list;
	}

	public function expiredUsersList(bool $include_hidden_categories = false): DynamicList
	{
		$list = $this->allUsersList();
		$conditions = sprintf('su.id_service = %d AND su.expiry_date < date()', $this->id());

		if (!$include_hidden_categories) {
			$conditions .= ' AND u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)';
		}

		$list->setConditions($conditions);
		return $list;
	}

	public function hasSubscriptions(): bool
	{
		return DB::getInstance()->test('services_users', 'id_service = ?', $this->id());
	}

	public function getUsers(bool $paid_only = false) {
		$where = $paid_only ? 'AND paid = 1' : '';
		$id_field = DynamicFields::getNameFieldsSQL('u');
		$sql = sprintf('SELECT su.id_user, %s FROM services_users su INNER JOIN users u ON u.id = su.id_user WHERE su.id_service = ? %s;', $id_field, $where);
		return DB::getInstance()->getAssoc($sql, $this->id());
	}

	public function long_label(): string
	{
		return Services::getLongLabel($this);
	}
}
