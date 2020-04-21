<?php

namespace Garradin\Entities\Accounting;

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

	const TYPE_ANALYTICAL = 4;
	const TYPE_VOLUNTEERING = 5;

	const TYPE_EXPENSE = 6;
	const TYPE_REVENUE = 7;

	protected $id;
	protected $id_plan;
	protected $code;
	protected $parent;
	protected $label;
	protected $position;
	protected $type;
	protected $user;

	protected $_types = [
		'id_plan'     => 'int',
		'code'        => 'string',
		'parent'      => '?int',
		'label'       => 'string',
		'description' => '?string',
		'position'    => 'int',
		'type'        => 'int',
		'user'        => 'int',
	];

	protected $_validation_rules = [
		'id_plan'     => 'required|integer|in_table:acc_plans,id',
		'code'        => 'required|string|alpha_num',
		'label'       => 'required|string|max:200',
		'description' => 'string|max:2000',
		'parent'      => 'required|nullable|integer|in_table:acc_accounts,id',
		'position'    => 'required|integer',
		'type'        => 'required|integer',
		'user'        => 'integer|min:0|max:1',
	];
}