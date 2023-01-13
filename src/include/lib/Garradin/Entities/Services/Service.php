<?php

namespace Garradin\Entities\Services;

use Garradin\DB;
use Garradin\DynamicList;
use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\Utils;
use Garradin\Users\DynamicFields;
use Garradin\Services\Fees;

class Service extends Entity
{
	const NAME = 'Activité';
	const PRIVATE_URL = '!services/fees/?id=%d';

	const TABLE = 'services';

	protected $id;
	protected $label;
	protected $description;
	protected $duration;
	protected $start_date;
	protected $end_date;

	protected $_types = [
		'id'          => 'int',
		'label'       => 'string',
		'description' => '?string',
		'duration'    => '?int',
		'start_date'  => '?date',
		'end_date'    => '?date',
	];

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

	public function allUsersList(): DynamicList
	{
		$id_field = DynamicFields::getNameFieldsSQL('u');
		$columns = [
			'id_user' => [
			],
			'end_date' => [
			],
			'user_number' => [
				'label' => 'Numéro de membre',
				'select' => 'm.numero',
				'export_only' => true,
			],
			'identity' => [
				'label' => 'Membre',
				'select' => $id_field,
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
		];

		$tables = 'services_users su
			INNER JOIN users u ON u.id = su.id_user
			INNER JOIN services s ON s.id = su.id_service
			LEFT JOIN services_fees sf ON sf.id = su.id_fee
			INNER JOIN (SELECT id, MAX(date) FROM services_users GROUP BY id_user, id_service) AS su2 ON su2.id = su.id';
		$conditions = sprintf('su.id_service = %d
			AND u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)', $this->id());

		$list = new DynamicList($columns, $tables, $conditions);
		$list->groupBy('su.id_user');
		$list->orderBy('paid', true);
		$list->setCount('COUNT(DISTINCT su.id_user)');
		return $list;
	}

	public function activeUsersList(): DynamicList
	{
		$list = $this->allUsersList();
		$conditions = sprintf('su.id_service = %d AND (su.expiry_date >= date() OR su.expiry_date IS NULL)
			AND su.paid = 1
			AND u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)', $this->id());
		$list->setConditions($conditions);
		return $list;
	}

	public function unpaidUsersList(): DynamicList
	{
		$list = $this->allUsersList();
		$conditions = sprintf('su.id_service = %d AND su.paid = 0 AND u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)', $this->id());
		$list->setConditions($conditions);
		return $list;
	}

	public function expiredUsersList(): DynamicList
	{
		$list = $this->allUsersList();
		$conditions = sprintf('su.id_service = %d AND su.expiry_date < date() AND u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)', $this->id());
		$list->setConditions($conditions);
		return $list;
	}

	public function getUsers(bool $paid_only = false) {
		$where = $paid_only ? 'AND paid = 1' : '';
		$id_field = DynamicFields::getNameFieldsSQL('u');
		$sql = sprintf('SELECT su.id_user, %s FROM services_users su INNER JOIN users u ON u.id = su.id_user WHERE su.id_service = ? %s;', $id_field, $where);
		return DB::getInstance()->getAssoc($sql, $this->id());
	}

	public function long_label(): string
	{
		if ($this->duration) {
			$duration = sprintf('%d jours', $this->duration);
		}
		elseif ($this->start_date)
			$duration = sprintf('du %s au %s', $this->start_date->format('d/m/Y'), $this->end_date->format('d/m/Y'));
		else {
			$duration = 'ponctuelle';
		}

		return sprintf('%s — %s', $this->label, $duration);
	}
}
