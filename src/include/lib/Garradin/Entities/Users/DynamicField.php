<?php

namespace Garradin\Entities\Users;

use Garradin\Config;
use Garradin\DB;
use Garradin\Entity;
use Garradin\Utils;
use Garradin\Users\DynamicFields;

class DynamicField extends Entity
{
	const TABLE = 'config_users_fields';

	protected int $id;
	protected string $name;

	/**
	 * Order of field in form
	 * @var int
	 */
	protected int $sort_order;

	protected string $type;
	protected string $label;
	protected ?string $help;

	/**
	 * TRUE if the field is required
	 */
	protected bool $required;

	/**
	 * 0 = only admins can read this field (private)
	 * 1 = admins + the user themselves can read it
	 */
	protected int $read_access;

	/**
	 * 0 = only admins can write this field
	 * 1 = admins + the user themselves can change it
	 */
	protected int $write_access;

	/**
	 * Use in users list table?
	 */
	protected bool $list_table;

	/**
	 * Multiple options (JSON) for select and multiple fields
	 */
	protected ?array $options = [];

	/**
	 * Default value
	 */
	protected ?string $default_value;

	/**
	 * System use
	 */
	protected int $system = 0;

	const PASSWORD = 0x01;
	const LOGIN = 0x02;
	const NUMBER = 0x04;
	const NAME = 0x08;
	const PRESET = 0x16;

	const ACCESS_ADMIN = 0;
	const ACCESS_USER = 1;

	const TYPES = [
		'email'		=>	'Adresse E-Mail',
		'url'		=>	'Adresse URL',
		'checkbox'	=>	'Case à cocher',
		'date'		=>	'Date',
		'datetime'	=>	'Date et heure',
		'month'     =>  'Mois et année',
		'file'      =>  'Fichier',
		'password'  =>  'Mot de passe',
		'number'	=>	'Nombre',
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
		'datetime' => '?DateTime',
		'file'     => '?string',
		'password' => '?string',
		'number'   => '?int|float',
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
		'number'   => 'INTEGER',
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
		'month'    => '%1s IS NULL OR (date(%1s || \'-03\') = %1$s || \'-03\')', // Use 3rd day to avoid any potential issue with timezones
	];

	const SYSTEM_FIELDS = [
		'id'           => '?int',
		'id_category'  => 'int',
		'pgp_key'      => '?string',
		'otp_secret'   => '?string',
		'date_login'   => '?DateTime',
		'date_created' => '?date',
	];

	public function delete(): bool
	{
		if ($this->system) {
			throw new ValidationException('Ce champ est utilisé en interne, il n\'est pas possible de le supprimer');
		}

		if ($this->type == 'file') {
			foreach (Files::glob(File::CONTEXT_USER . '/*/' . $this->name) as $file) {
				$file->delete();
			}
		}

		return parent::delete();
	}

	public function selfCheck(): void
	{
		$this->name = strtolower($this->name);

		$this->assert($this->read_access == self::ACCESS_ADMIN || $this->read_access == self::ACCESS_USER);
		$this->assert($this->write_access == self::ACCESS_ADMIN || $this->write_access == self::ACCESS_USER);

		$this->assert(!array_key_exists($this->name, self::SYSTEM_FIELDS), 'Ce nom de champ est déjà utilisé par un champ système, merci d\'en choisir un autre.');
		$this->assert(preg_match('!^[a-z][a-z0-9]*(_[a-z0-9]+)*$!', $this->name), 'Le nom du champ est invalide : ne sont acceptés que les lettres minuscules et les chiffres (éventuellement séparés par un underscore).');

		$this->assert(trim($this->label) != '', 'Le libellé est obligatoire.');
		$this->assert($this->type && array_key_exists($this->type, self::TYPES), 'Type de champ invalide.');

		if ($this->system & self::PASSWORD) {
			$this->assert($this->type == 'password', 'Le champ mot de passe ne peut être d\'un type différent de mot de passe.');
		}

		$this->assert(!($this->type == 'multiple' || $this->type == 'select') || !empty($this->options), 'Le champ nécessite de comporter au moins une option possible.');

		$db = DB::getInstance();

		if (!$this->exists()) {
			$this->assert(!$db->test(self::TABLE, 'name = ?', $this->name), 'Ce nom de champ est déjà utilisé par un autre champ: ' . $this->name);
		}
		else {
			$this->assert(!$db->test(self::TABLE, 'name = ? AND id != ?', $this->name, $this->id()), 'Ce nom de champ est déjà utilisé par un autre champ.');
		}

		$this->assert($this->exists() || $this->system & self::PRESET || !array_key_exists($this->name, DynamicFields::getInstance()->getPresets()), 'Ce nom de champ est déjà utilisé par un champ pré-défini.');

		if ($this->exists()) {
			$this->assert(!isset($this->_modified['type']));
			$this->assert(!isset($this->_modified['name']));
		}
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		$source['required'] = !empty($source['required']) ? true : false;
		$source['list_table'] = !empty($source['list_table']) ? true : false;

		return parent::importForm($source);
	}
}
