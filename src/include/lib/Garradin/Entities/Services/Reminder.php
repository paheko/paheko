<?php

namespace Garradin\Entities\Services;

use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\Config;

class Reminder extends Entity
{
	const TABLE = 'services_reminders';

	protected $id;
	protected $id_service;
	protected $delay;
	protected $subject;
	protected $body;

	protected $_types = [
		'id'         => 'int',
		'id_service' => 'int',
		'delay'      => 'int',
		'subject'    => 'string',
		'body'       => 'string',
	];

	protected $_form_rules = [
		'subject' => 'string|max:200|required',
		'body'    => 'string|max:64000|required',
		'delay'   => 'numeric|required',
	];

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert($this->id_service, 'Aucun service n\'a été indiqué pour ce tarif.');
		$this->assert(strlen($this->subject));
		$this->assert(strlen($this->body));
		$this->assert($this->delay !== null);
	}

	public function service()
	{
		return EntityManager::findOneById(Service::class, $this->id_service);
	}
}
