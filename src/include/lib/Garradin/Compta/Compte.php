<?php

namespace Garradin\Compta;

use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Compte extends Entity
{
	const PASSIF = 1;
	const ACTIF = 2;
	const ACTIF_PASSIF = 3;

	const PRODUIT = 4;
	const CHARGE = 5;
	const PRODUIT_CHARGE = 6;

	const BANQUE = 7;
	const CAISSE = 8;

	const A_ENCAISSER = 9;

	protected $id;
	protected $parent;
	protected $libelle;
	protected $position;
	protected $plan_comptable;

	protected $_fields = [
		'id'             => 'required|string|alpha_num',
		'libelle'        => 'required|string',
		'parent'         => 'required|string|alpha_num|in_table:comptes,id',
		'position'       => 'required|integer',
		'plan_comptable' => 'integer|min:0|max:1',
	];
}