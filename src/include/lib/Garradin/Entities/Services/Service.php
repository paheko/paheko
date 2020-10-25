<?php

namespace Garradin\Entities\Services;

use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\Utils;

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

	protected $_form_rules = [
		'label'       => 'string|max:200|required',
		'description' => 'string|max:2000',
		'duration'    => 'numeric|min:0',
		'start_date'  => 'date_format:d/m/Y',
		'end_date'    => 'date_format:d/m/Y',
	];

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert(!isset($this->duration, $this->start_date, $this->end_date) || $this->duration || ($this->start_date && $this->end_date), 'Seulement une option doit être choisie : durée ou dates de début et de fin de validité');

		$this->assert(null === $this->duration || $this->duration > 0);
		$this->assert(null === $this->start_date || $this->end_date > $this->start_date, 'La date de fin de validité doit être après la date de début');
	}

	public function registerUser(int $user_id, \DateTime $expiry_date)
	{
		$db = DB::getInstance();
		$db->preparedQuery('INSERT IGNORE INTO services_users (id_user, id_service, expiry_date) VALUES (?, ?, ?);', $user_id, $this->id(), $expiry_date->format('Y-m-d'));
	}

	public function importForm(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (!empty($source['duration'])) {
			unset($source['start_date'], $source['end_date']);
		}
		elseif (!empty($source['start_date'])) {
			unset($source['duration']);
		}
		else {
			unset($source['start_date'], $source['end_date'], $source['duration']);
		}

		parent::importForm($source);
	}
}
