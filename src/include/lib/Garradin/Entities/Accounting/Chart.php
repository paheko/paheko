<?php

namespace Garradin\Entities\Accounting;

use Garradin\Entity;
use Garradin\Utils;
use Garradin\Accounting\Accounts;

class Chart extends Entity
{
	const TABLE = 'acc_charts';

	protected $id;
	protected $label;
	protected $country;
	protected $code;
	protected $archived = 0;

	protected $_types = [
		'id'       => 'int',
		'label'    => 'string',
		'country'  => 'string',
		'code'     => '?string',
		'archived' => 'int',
	];

	protected $_form_rules = [
		'label'    => 'required|string|max:200',
		'country'  => 'required|string|size:2',
		'code'     => 'string',
	];

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert(Utils::getCountryName($this->country), 'Le code pays doit être un code ISO valide');
		$this->assert($this->archived === 0 || $this->archived === 1);
	}

	public function accounts()
	{
		return new Accounts($this->id());
	}
}
