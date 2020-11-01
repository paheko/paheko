<?php

namespace Garradin\Entities\Services;

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
}
