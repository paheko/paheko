<?php

namespace Garradin\Entities\Compta;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Account extends Entity
{
	const TABLE = 'acc_accounts';

	const PASSIF = 1;
	const ACTIF = 2;
	const ACTIF_PASSIF = 3;

	const PRODUIT = 4;
	const CHARGE = 5;
	const PRODUIT_CHARGE = 6;

	const TYPE_NONE = 0;
	const TYPE_BANK = 1;
	const TYPE_CASH = 2;

	/**
	 * Outstanding transaction accounts (like cheque or card payments)
	 */
	const TYPE_OUTSTANDING = 3;

	protected $id;
	protected $id_plan;
	protected $code;
	protected $parent;
	protected $label;
	protected $position;
	protected $type;
	protected $user;

	protected $_types = [
		'id_plan'  => 'int',
		'code'     => 'string',
		'parent'   => '?int',
		'label'    => 'string',
		'type' => 'int',
		'special'  => 'int',
		'user'     => 'int',
	];

	protected $_validation_rules = [
		'id'       => 'required|integer',
		'libelle'  => 'required|string',
		'parent'   => 'required|nullable|integer|in_table:acc_accounts,id',
		'id_plan'  => 'required|integer|in_table:acc_plans,id',
		'type' => 'required|integer',
		'special'  => 'required|integer',
		'user'     => 'integer|min:0|max:1',
	];
}