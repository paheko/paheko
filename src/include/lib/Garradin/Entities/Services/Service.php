<?php

namespace Garradin\Entities\Services;

use Garradin\Config;
use Garradin\DynamicList;
use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\Utils;
use Garradin\Services\Fees;

class Service extends Entity
{
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
		$this->assert(trim($this->label) !== '', 'Le libellé doit être renseigné');
		$this->assert(strlen($this->label) <= 200, 'Le libellé doit faire moins de 200 caractères');
		$this->assert(strlen($this->description) <= 2000, 'La description doit faire moins de 2000 caractères');
		$this->assert(!isset($this->duration, $this->start_date, $this->end_date) || $this->duration || ($this->start_date && $this->end_date), 'Seulement une option doit être choisie : durée ou dates de début et de fin de validité');
		$this->assert(null === $this->start_date || $this->start_date instanceof \DateTimeInterface);
		$this->assert(null === $this->end_date || $this->end_date instanceof \DateTimeInterface);
		$this->assert(null === $this->duration || (is_int($this->duration) && $this->duration > 0), 'La durée n\'est pas valide');
		$this->assert(null === $this->start_date || $this->end_date > $this->start_date, 'La date de fin de validité doit être après la date de début');
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

	public function paidUsersList(): DynamicList
	{
		$identity = Config::getInstance()->get('champ_identite');
		$columns = [
			'id_user' => [
			],
			'end_date' => [
			],
			'identity' => [
				'label' => 'Membre',
				'select' => 'm.' . $identity,
				'order' => sprintf('transliterate_to_ascii(m.%s) COLLATE NOCASE %%s', $identity),
			],
			'status' => [
				'label' => 'Statut',
				'select' => 'CASE WHEN su.expiry_date < date() THEN -1 WHEN su.expiry_date >= date() THEN 1 ELSE 0 END',
			],
			'paid' => [
				'label' => 'Payé ?',
				'select' => 'su.paid',
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
			INNER JOIN membres m ON m.id = su.id_user
			INNER JOIN services s ON s.id = su.id_service
			INNER JOIN services_fees sf ON sf.id = su.id_fee
			INNER JOIN (SELECT id, MAX(date) FROM services_users GROUP BY id_user, id_service) AS su2 ON su2.id = su.id';
		$conditions = sprintf('su.id_service = %d AND su.paid = 1 AND (su.expiry_date >= date() OR su.expiry_date IS NULL)
			AND m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)', $this->id());

		$list = new DynamicList($columns, $tables, $conditions);
		$list->groupBy('su.id_user');
		$list->orderBy('date', true);
		$list->setCount('COUNT(DISTINCT su.id_user)');
		return $list;
	}

	public function unpaidUsersList(): DynamicList
	{
		$list = $this->paidUsersList();
		$conditions = sprintf('su.id_service = %d AND su.paid = 0 AND m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)', $this->id());
		$list->setConditions($conditions);
		return $list;
	}

	public function expiredUsersList(): DynamicList
	{
		$list = $this->paidUsersList();
		$conditions = sprintf('su.id_service = %d AND su.expiry_date < date() AND m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)', $this->id());
		$list->setConditions($conditions);
		return $list;
	}
}
