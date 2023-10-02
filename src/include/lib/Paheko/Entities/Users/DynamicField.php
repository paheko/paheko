<?php
declare(strict_types=1);

namespace Paheko\Entities\Users;

use Paheko\Config;
use Paheko\DB;
use Paheko\Entity;
use Paheko\Utils;
use Paheko\ValidationException;
use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

use KD2\DB\Date;

class DynamicField extends Entity
{
	const NAME = 'Champ de fiche membre';

	const TABLE = 'config_users_fields';

	protected ?int $id;
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
	protected bool $required = false;

	/**
	 * Maps to Session::ACCESS_LEVEL_*
	 */
	protected int $user_access_level = 0;

	/**
	 * Maps to Session::ACCESS_LEVEL_*
	 */
	protected int $management_access_level = 1;

	/**
	 * Use in users list table?
	 */
	protected bool $list_table = false;

	/**
	 * Multiple options (JSON) for select and multiple fields
	 */
	protected ?array $options = [];

	/**
	 * Default value
	 */
	protected ?string $default_value;

	/**
	 * SQL code for virtual view
	 */
	protected ?string $sql;

	/**
	 * System use
	 */
	protected int $system = 0;

	const PASSWORD = 0x01 << 1;
	const LOGIN    = 0x01 << 2;
	const NUMBER   = 0x01 << 3;
	const NAMES    = 0x01 << 4;
	const PRESET   = 0x01 << 5;

	const ACCESS_USER = 0x01 << 1;
	const ACCESS_READ = 0x01 << 2;
	const ACCESS_WRITE = 0x01 << 3;
	const ACCESS_ADMIN = 0x01 << 4;

	const TYPES = [
		'email'		=>	'Adresse E-Mail',
		'url'		=>	'Adresse URL',
		'checkbox'	=>	'Case à cocher',
		'date'		=>	'Date',
		'datetime'	=>	'Date et heure',
		'month'     =>  'Mois et année',
		'year'      =>  'Année',
		'file'      =>  'Fichier',
		'password'  =>  'Mot de passe',
		'number'	=>	'Nombre',
		'tel'		=>	'Numéro de téléphone',
		'select'	=>	'Sélecteur à choix unique',
		'multiple'  =>  'Sélecteur à choix multiple',
		'country'	=>	'Sélecteur de pays',
		'text'		=>	'Texte',
		'textarea'	=>	'Texte multi-lignes',
		//'virtual' =>  'Calculé',
	];

	const PHP_TYPES = [
		'email'    => '?string',
		'url'      => '?string',
		'checkbox' => 'bool',
		'date'     => '?' . Date::class,
		'datetime' => '?DateTime',
		'month'    => '?string',
		'year'     => '?int',
		'file'     => '?string',
		'password' => '?string',
		'number'   => '?int',
		'tel'      => '?string',
		'select'   => '?string',
		'multiple' => '?int',
		'country'  => '?string',
		'text'     => '?string',
		'textarea' => '?string',
		//'virtual'=> 'dynamic',
	];

	const SQL_TYPES = [
		'email'    => 'TEXT',
		'url'      => 'TEXT',
		'checkbox' => 'INTEGER NOT NULL DEFAULT 0',
		'date'     => 'TEXT',
		'datetime' => 'TEXT',
		'month'    => 'TEXT',
		'year'     => 'INTEGER',
		'file'     => 'TEXT',
		'password' => 'TEXT',
		'number'   => 'INTEGER',
		'tel'      => 'TEXT',
		'select'   => 'TEXT',
		'multiple' => 'INTEGER',
		'country'  => 'TEXT',
		'text'     => 'TEXT',
		'textarea' => 'TEXT',
		//'virtual'  => null,
	];

	const SEARCH_TYPES = [
		'email',
		'text',
		'textarea',
		'url',
	];

	const LOGIN_FIELD_TYPES = [
		'email',
		'url',
		'text',
		'number',
		'tel',
	];

	const NAME_FIELD_TYPES = [
		'text',
		'select',
		'number',
		'url',
		'email',
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
		'date_updated' => '?DateTime',
		'id_parent'    => '?int',
		'is_parent'    => 'bool',
		'preferences'  => '?stdClass',
	];

	const SYSTEM_FIELDS_SQL = [
		'id INTEGER PRIMARY KEY,',
		'id_category INTEGER NOT NULL REFERENCES users_categories(id),',
		'date_login TEXT NULL CHECK (date_login IS NULL OR datetime(date_login) = date_login),',
		'date_updated TEXT NULL CHECK (date_updated IS NULL OR datetime(date_updated) = date_updated),',
		'otp_secret TEXT NULL,',
		'pgp_key TEXT NULL,',
		'id_parent INTEGER NULL REFERENCES users(id) ON DELETE SET NULL CHECK (id_parent IS NULL OR is_parent = 0),',
		'is_parent INTEGER NOT NULL DEFAULT 0,',
		'preferences TEXT NULL,'
	];

	public function sql_type(): string
	{
		if ($this->type == 'checkbox') {
			return 'INTEGER';
		}

		return self::SQL_TYPES[$this->type];
	}

	public function delete(): bool
	{
		if (!$this->canDelete()) {
			throw new ValidationException('Ce champ est utilisé en interne, il n\'est pas possible de le supprimer');
		}

		if ($this->type == 'file') {
			// Delete all linked files
			$glob = sprintf('%s/*/%s/', File::CONTEXT_USER, $this->name);

			foreach (Files::glob($glob) as $file) {
				$file->delete();
			}
		}

		return parent::delete();
	}

	public function canSetDefaultValue(): bool
	{
		return in_array($this->type ?? null, ['text', 'textarea', 'number', 'select', 'multiple']);
	}

	public function isPreset(): bool
	{
		return (bool) ($this->system & self::PRESET);
	}

	public function isVirtual(): bool
	{
		return $this->type == 'virtual';
	}

	public function canDelete(): bool
	{
		if ($this->system & self::PASSWORD || $this->system & self::NUMBER || $this->system & self::NAMES || $this->system & self::LOGIN) {
			return false;
		}

		return true;
	}

	public function hasSearchCache(): bool
	{
		return in_array($this->type, DynamicField::SEARCH_TYPES);
	}

	public function selfCheck(): void
	{
		// Disallow name change if the field exists
		if ($this->exists()) {
			$this->assert(!$this->isModified('name'));
			$this->assert(!$this->isModified('type'));
		}

		$this->name = strtolower($this->name);

		$this->assert(in_array($this->management_access_level, Session::ACCESS_LEVELS, true));
		$this->assert(in_array($this->user_access_level, Session::ACCESS_LEVELS, true));

		$this->assert(!array_key_exists($this->name, self::SYSTEM_FIELDS), 'Ce nom de champ est déjà utilisé par un champ système, merci d\'en choisir un autre.');
		$this->assert(preg_match('!^[a-z][a-z0-9]*(_[a-z0-9]+)*$!', $this->name), 'Le nom du champ est invalide : ne sont acceptés que les lettres minuscules et les chiffres (éventuellement séparés par un underscore).');

		$this->assert(trim($this->label) != '', 'Le libellé est obligatoire.');
		$this->assert($this->type && array_key_exists($this->type, self::TYPES), 'Type de champ invalide.');

		if ($this->system & self::PASSWORD) {
			$this->assert($this->type == 'password', 'Le champ mot de passe ne peut être d\'un type différent de mot de passe.');
		}

		if ($this->type == 'multiple' || $this->type == 'select') {
			$this->options = array_filter($this->options);
			$this->assert(is_array($this->options) && count($this->options) >= 1 && trim((string)current($this->options)) !== '', 'Ce champ nécessite de comporter au moins une option possible: ' . $this->name);
		}

		$db = DB::getInstance();

		if (!$this->exists()) {
			$this->assert(!$db->test(self::TABLE, 'name = ?', $this->name), 'Ce nom de champ est déjà utilisé par un autre champ: ' . $this->name);
		}
		else {
			$this->assert(!$db->test(self::TABLE, 'name = ? AND id != ?', $this->name, $this->id()), 'Ce nom de champ est déjà utilisé par un autre champ.');
		}

		if ($this->exists()) {
			$this->assert($this->system & self::PRESET || !array_key_exists($this->name, DynamicFields::getInstance()->getPresets()), 'Ce nom de champ est déjà utilisé par un champ pré-défini.');
		}

		/* FIXME TODO
		if (self::SQL_TYPES[$this->type] == 'VIRTUAL') {
			$this->assert(null !== $this->sql && strlen(trim($this->sql)), 'Le code SQL est manquant');

			try {
				$db->protectSelect(['users' => []], sprintf('SELECT (%s) FROM users;', $this->sql));
			}
			catch (\KD2\DB\DB_Exception $e) {
				throw new ValidationException('Le code SQL du champ calculé est invalide: ' . $e->getMessage(), 0, $e);
			}

			try {
				$fields = DynamicFields::getInstance()->getKeys();
				$fields = array_map([DB::getInstance(), 'quoteIdentifier'], $fields);
				$fields = implode(', ', $fields);
				$db->exec(sprintf('DROP TABLE IF EXISTS test_generated_column;
					CREATE TEMP TABLE test_generated_column (%s, a GENERATED ALWAYS AS (%s) VIRTUAL);
					SELECT 1 FROM test_generated_column;
					DROP TABLE IF EXISTS test_generated_column;', $fields, $this->sql));
			}
			catch (\KD2\DB\DB_Exception $e) {
				throw new ValidationException('Le code SQL du champ calculé est invalide 2: ' . $e->getMessage(), 0, $e);
			}
		}
		*/
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

	public function getStringValue($value): ?string
	{
		switch ($this->type) {
			case 'multiple':
				// Useful for search results, if a value is not a number
				if (!is_numeric($value)) {
					return '';
				}

				$out = [];

				foreach ($this->options as $b => $name)
				{
					if ($value & (0x01 << $b))
						$out[] = $name;
				}

				return implode(', ', $out);
			case 'checkbox':
				return $value ? 'Oui' : '';
			case 'date':
				if (is_object($value) && $value instanceof \DateTimeInterface) {
					return $value->format('d/m/Y');
				}

				return (string) $value;
			case 'datetime':
				if (is_object($value) && $value instanceof \DateTimeInterface) {
					return $value->format('d/m/Y à H:i');
				}

				return (string) $value;
			case 'country':
				return Utils::getCountryName($value);
			default:
				return $value === null ? null : (string) $value;
		}
	}
}
