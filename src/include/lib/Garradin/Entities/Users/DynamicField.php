<?php

namespace Garradin\Users;

use Garradin\Config;
use Garradin\DB;
use Garradin\Entity;
use Garradin\Utils;
use Garradin\Users\DynamicFields;

class DynamicField extends Entity
{
	const TABLE = 'config_users_fields';

	protected $name;
	protected $order;
	protected $type;
	protected $label;
	protected $help;
	protected $mandatory;
	protected $private;
	protected $user_editable;
	protected $list_row;
	protected $options;
	protected $system;

	protected $_types = [
		'name'          => 'string',
		'order'         => 'int',
		'type'          => 'string',
		'label'         => 'string',
		'help'          => '?string',
		'mandatory'     => 'bool',
		'private'       => 'bool',
		'user_editable' => 'bool',
		'list_row'      => '?int',
		'options'       => '?string',
		'system'        => '?string',
	];


	const TYPES = [
		'email'		=>	'Adresse E-Mail',
		'url'		=>	'Adresse URL',
		'checkbox'	=>	'Case à cocher',
		'date'		=>	'Date',
		'datetime'	=>	'Date et heure',
		'file'      =>  'Fichier',
		'password'  =>  'Mot de passe',
		'number'	=>	'Numéro',
		'tel'		=>	'Numéro de téléphone',
		'select'	=>	'Sélecteur à choix unique',
		'multiple'  =>  'Sélecteur à choix multiple',
		'country'	=>	'Sélecteur de pays',
		'text'		=>	'Texte',
		'textarea'	=>	'Texte multi-lignes',
	];

	const PHP_TYPES = [
		'email'    => '?string',
		'url'      => '?string',
		'checkbox' => 'int',
		'date'     => '?date',
		'datetime' => '?datetime',
		'file'     => '?string',
		'password' => '?string',
		'number'   => '?string',
		'tel'      => '?string',
		'select'   => '?string',
		'multiple' => 'int',
		'country'  => '?string',
		'text'     => '?string',
		'textarea' => '?string',
	];

	const SQL_TYPES = [
		'email'    => 'TEXT',
		'url'      => 'TEXT',
		'checkbox' => 'INTEGER NOT NULL DEFAULT 0',
		'date'     => 'TEXT',
		'datetime' => 'TEXT',
		'file'     => 'TEXT',
		'password' => 'TEXT',
		'number'   => 'TEXT',
		'tel'      => 'TEXT',
		'select'   => 'TEXT',
		'multiple' => 'INTEGER NOT NULL DEFAULT 0',
		'country'  => 'TEXT',
		'text'     => 'TEXT',
		'textarea' => 'TEXT',
	];

	const SQL_CONSTRAINTS = [
		'checkbox' => '%1s = 1 OR %1s = 0',
		'date'     => '%1s IS NULL OR (date(%1$s) IS NOT NULL AND date(%1s) = %1$s)',
		'datetime' => '%1s IS NULL OR (date(%1$s) IS NOT NULL AND date(%1s) = %1$s)',
	];

	const SYSTEM_FIELDS = [
		'id'           => 'int',
		'id_category'  => 'int',
		'pgp_key'      => '?string',
		'otp_secret'   => '?string',
		'date_login'   => '?DateTime',
		'date_created' => '?DateTime',
	];

	public function delete(): bool
	{
		if ($this->system) {
			throw new ValidationException('Ce champ est utilisé en interne, il n\'est pas possible de le supprimer');
		}

		parent::delete();
	}

	public function selfCheck(): void
	{
		$this->name = strtolower($this->name);

		$this->assert(!array_key_exists($this->name, self::SYSTEM_FIELDS), 'Ce nom de champ est déjà utilisé par un champ système, merci d\'en choisir un autre.');
		$this->assert(preg_match('!^[a-z][a-z0-9]*(_[a-z0-9]+)*$!', $this->name), 'Le nom du champ est invalide : ne sont acceptés que les lettres minuscules et les chiffres (éventuellement séparés par un underscore).');

		$this->assert(trim($this->label) != '', 'Le libellé est obligatoire.');
		$this->assert($this->type && array_key_exists($this->type, self::TYPES), 'Type de champ invalide.');
		$this->assert($this->system != 'password' || $this->type == 'password', 'Le champ mot de passe ne peut être d\'un type différent de mot de passe.');

		$this->assert(!($this->type == 'multiple' || $this->type == 'select') || !empty($this->options), 'Le champ nécessite de comporter au moins une option possible.');

		$db = DB::getInstance();

		$this->asserts($this->exists() || $this->system == 'preset' || !array_key_exists($this->name, $this->getPresets()), 'Ce nom de champ est déjà utilisé par un champ pré-défini.');
		$this->asserts(!$this->exists() && !$db->test(self::TABLE, 'name = ?', $this->name), 'Ce nom de champ est déjà utilisé par un autre champ.');
		$this->asserts($this->exists() && !$db->test(self::TABLE, 'name = ? AND id != ?', $this->name, $this->id()), 'Ce nom de champ est déjà utilisé par un autre champ.');
	}
}
