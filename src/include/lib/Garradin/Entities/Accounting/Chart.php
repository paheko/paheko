<?php

namespace Garradin\Entities\Accounting;

use Garradin\DB;
use Garradin\Entity;
use Garradin\Utils;
use Garradin\ValidationException;
use Garradin\UserException;
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

	public function selfCheck(): void
	{
		$this->assert(trim($this->label) !== '', 'Le libellé ne peut rester vide.');
		$this->assert(strlen($this->label) <= 200, 'Le libellé ne peut faire plus de 200 caractères.');
		$this->assert(trim($this->country) !== '', 'Le pays ne peut rester vide.');
		$this->assert(Utils::getCountryName($this->country), 'Le code pays doit être un code ISO valide');
		$this->assert($this->archived === 0 || $this->archived === 1);
		parent::selfCheck();
	}

	public function accounts()
	{
		return new Accounts($this->id());
	}

	public function canDelete()
	{
		return !DB::getInstance()->firstColumn(sprintf('SELECT 1 FROM %s WHERE id_chart = ? LIMIT 1;', Year::TABLE), $this->id());
	}
}
