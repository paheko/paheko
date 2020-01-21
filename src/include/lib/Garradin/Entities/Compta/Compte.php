<?php

namespace Garradin\Entities\Compta;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Compte extends Entity
{
	const TABLE = 'compta_comptes';

	const PASSIF = 1;
	const ACTIF = 2;
	const ACTIF_PASSIF = 3;

	const PRODUIT = 4;
	const CHARGE = 5;
	const PRODUIT_CHARGE = 6;

	// Types spéciaux de comptes
	const BANQUE = 7;
	const CAISSE = 8;

	const A_ENCAISSER = 9;

	protected $id;
	protected $code;
	protected $parent;
	protected $libelle;
	protected $position;
	protected $plan_comptable;
	protected $id_exercice;

	protected $_types = [
		'code'           => 'string',
		'parent'         => '?int',
		'libelle'        => 'string',
		'position'       => 'int',
		'plan_comptable' => 'int',
		'id_exercice'    => '?int',
	];

	protected $_validation_rules = [
		'id'             => 'required|integer',
		'libelle'        => 'required|string',
		'parent'         => 'required|integer|in_table:compta_comptes,id',
		'position'       => 'required|integer',
		'plan_comptable' => 'integer|min:0|max:1',
		'id_exercice'    => 'integer|in_table:compta_exercices,id'
	];
}