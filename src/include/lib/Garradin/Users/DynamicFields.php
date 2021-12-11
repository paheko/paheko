<?php

namespace Garradin\Users;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;

use Garradin\Entities\Users\DynamicField;
use Garradin\Entities\Users\User;

use const Garradin\ROOT;

class DynamicFields
{
	const PRESETS_FILE = ROOT . '/include/data/users_fields_presets.ini';

	const TABLE = DynamicField::TABLE;

	protected $_fields;
	protected $_fields_by_type;
	protected $_fields_by_system_use;
	protected $_presets;

	static protected $_instance;

	static public function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Returns the list of columns containing an email address (there might be more than one)
	 * @return array
	 */
	static public function getEmailFields(): array
	{
		return array_keys(self::getInstance()->fieldsByType('email'));
	}

	static public function getFirstEmailField(): array
	{
		return key(self::getInstance()->fieldsByType('email'));
	}

	static public function getNumberField(): string
	{
		return key(self::getInstance()->fieldsBySystemUse('number'));
	}

	static public function getLoginField(): string
	{
		return key(self::getInstance()->fieldsBySystemUse('login'));
	}

	static public function getNameFields(): array
	{
		return array_keys(self::getInstance()->fieldsBySystemUse('name'));
	}

	static public function getNameFieldsSQL(): string
	{
		return implode(' || \' \' ', array_keys(self::getInstance()->fieldsBySystemUse('name')));
	}

	static public function getEntityProperties(): array
	{
		$fields = self::getEntityTypes();
		return DynamicField::SYSTEM_FIELDS + $fields;
	}

	static public function changeLoginField(string $new_field): void
	{
		$old_field = self::getLoginField();

		if ($old_field === $new_field) {
			return;
		}

		$db = DB::getInstance();

		$sql = sprintf('UPDATE %s SET system = NULL WHERE system = \'login\';
			UPDATE %1$s SET system = \'login\' WHERE key = %s;',
			self::TABLE,
			$new_field
		);

		$db->exec($sql);

		// Regenerate login index
		$db->exec('DROP INDEX IF EXISTS users_id_field;');
		$this->createIndexes();
	}

	protected function __construct(bool $load = true)
	{
		if ($load) {
			$this->reload();
		}
	}

	protected function reload()
	{
		$db = DB::getInstance();
		$this->_fields = $db->getGrouped(sprintf('SELECT name, * FROM %s ORDER BY sort_order;', self::TABLE));
		$this->reloadCache();
	}

	protected function reloadCache()
	{
		$this->_fields_by_type = [];
		$this->_fields_by_system_use = [];

		foreach ($this->_fields as $key => &$field) {
			if (!isset($this->_fields_by_type[$field->type])) {
				$this->_fields_by_type[$field->type] = [];
			}

			$this->_fields_by_type[$field->type][$key] =& $field;

			if (!$field->system) {
				continue;
			}

			if (!isset($this->_fields_by_system_use[$field->system])) {
				$this->_fields_by_system_use[$field->system] = [];
			}

			$this->_fields_by_system_use[$field->system][$key] =& $field;
		}
	}

	protected function fieldsByType(string $type): array
	{
		return $this->_fields_by_type[$type] ?? [];
	}

	protected function fieldsBySystemUse(string $use): array
	{
		return $this->_fields_by_system_use[$use] ?? [];
	}

	public function getEntityTypes(): array
	{
		$types = [];

		foreach ($this->_fields as $key => $field) {
			$types[$key] = $field->type;
		}

		return $types;
	}

	public function getPresets(): array
	{
		if (null === $this->_presets)
		{
			$this->_presets = parse_ini_file(self::PRESETS_FILE, true);

			foreach ($this->_presets as &$preset) {
				$preset = (object) $preset;
			}

			unset($preset);
		}

		return $this->_presets;
	}

	public function listUnusedPresets(): array
	{
		return array_diff_key(self::getPresets(), (array) $this->_fields);
	}

	public function getInstallPresets()
	{
		return array_filter($this->getPresets(), fn ($row) => !$row->install );
	}

	/**
	 * Import from old INI config
	 */
	static public function fromOldINI(string $config, string $login_field, string $name_field, string $number_field)
	{
		$config = parse_ini_string($config, true);

		$i = 0;

		$self = new self(false);
		$fields = [
			'date_connexion'   => 'date_login',
			'date_inscription' => 'date_created',
			'clef_pgp'         => 'pgp_key',
			'secret_otp'       => 'otp_secret',
			'id_category'      => 'id_category',
		];

		$defaults = [
			'help'      => null,
			'private'   => false,
			'editable'  => true,
			'mandatory' => false,
			'list_row'  => null,
		];

		foreach ($config as $name => $data) {
			$field = new DynamicField;

			$fields[$name] = $name;

			if ($name == 'passe') {
				$name = 'password';
				$data['title'] = 'Mot de passe';
				$field->system = 'password';
				$fields['passe'] = 'password';
			}
			elseif ($name == $login_field) {
				$field->system = 'login';
			}
			elseif ($name == $name_field) {
				$field->system = 'name';
			}
			elseif ($name == $number_field) {
				$field->system = 'number';
			}

			$data = array_merge($defaults, $data);

			$field->set('name', $name);
			$field->set('label', $data['title']);
			$field->set('type', $data['type']);
			$field->set('help', empty($data['help']) ? null : $data['help']);
			$field->set('read_access', $data['private'] ? $field::ACCESS_ADMIN : $field::ACCESS_USER);
			$field->set('write_access', $data['editable'] ? $field::ACCESS_ADMIN : $field::ACCESS_USER);
			$field->set('required', (bool) $data['mandatory']);
			$field->set('list_row', isset($data['list_row']) ? (int)$data['list_row'] : null);
			$field->set('sort_order', $i++);
			$self->_fields[$name] = $field;
		}

		self::$_instance = $self;

		$self->createTable();
		$self->copy('membres', User::TABLE, $fields);

		return $self;
	}

	public function isText(string $field)
	{
		$type = $this->_fields[$field]->type;
		return self::TYPES[$type] == 'string';
	}

	public function getKeys()
	{
		return array_keys($this->_fields);
	}

	public function all()
	{
		return $this->_fields;
	}

	public function allExceptPassword()
	{
		return array_filter($this->_fields, function ($a) {
			return $a->system != 'password';
		});
	}

	public function listAssocNames()
	{
		$out = [];

		foreach ($this->_fields as $key => $field) {
			if ($field->system == 'password') {
				continue;
			}

			$out[$key] = $field->label;
		}

		return $out;
	}

	public function getMultiples()
	{
		return array_filter($this->_fields, function ($a) {
			return $a->type == 'multiple';
		});
	}

	public function getListedFields()
	{
		$champs = (array) $this->champs;

		$champs = array_filter($champs, function ($a) {
			return empty($a->list_row) ? false : true;
		});

		uasort($champs, function ($a, $b) {
			if ($a->list_row == $b->list_row)
				return 0;

			return ($a->list_row > $b->list_row) ? 1 : -1;
		});

		return (object) $champs;
	}

	public function getFirstListed()
	{
		foreach ($this->champs as $key=>$config)
		{
			if (empty($config->list_row))
			{
				continue;
			}

			return $key;
		}
	}



	public function getSQLSchema(string $table_name = User::TABLE): string
	{
		$db = DB::getInstance();

		// Champs à créer
		$create = [
			'id INTEGER PRIMARY KEY, -- Numéro attribué automatiquement',
			'id_category INTEGER NOT NULL REFERENCES users_categories(id),',
			'date_login TEXT NULL CHECK (date_login IS NULL OR datetime(date_login) = date_login), -- Date de dernière connexion',
			'date_created TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date_created) = date_created), -- Date d\'inscription',
			'otp_secret TEXT NULL, -- Code secret pour TOTP',
			'pgp_key TEXT NULL, -- Clé publique PGP'
		];

		end($this->_fields);
		$last_one = key($this->_fields);

		foreach ($this->_fields as $key => $cfg)
		{
			$type = DynamicField::SQL_TYPES[$cfg->type];
			$line = sprintf('%s %s', $db->quoteIdentifier($key), $type);

			if ($type == 'TEXT' && $cfg->type != 'password') {
				$line .= sprintf(",\n%s %s", $db->quoteIdentifier($key . '_search'), 'TEXT COLLATE NOCASE');
			}

			if ($last_one != $key) {
				$line .= ',';
			}

			if (!empty($cfg->title))
			{
				$line .= ' -- ' . str_replace(["\n", "\r"], '', $cfg->label);
			}

			$create[] = $line;
		}

		$sql = sprintf("CREATE TABLE %s\n(\n\t%s\n);", $table_name, implode("\n\t", $create));
		return $sql;
	}

	public function getCopyFields(): array
	{
		// Champs à recopier
		$copy = array_keys(DynamicField::SYSTEM_FIELDS);

		$db = DB::getInstance();
		// FIXME
		$anciens_champs = new Champs($db->firstColumn('SELECT value FROM config WHERE key = ?;', 'champs_membres'));
		$anciens_champs = is_null($anciens_champs) ? $this->champs : $anciens_champs->getAll();

		foreach ($this->champs as $key=>$cfg)
		{
			if (property_exists($anciens_champs, $key)) {
				$copy[$key] = $key;
			}
		}

		return $copy;
	}

	public function getSQLCopy(string $old_table_name, string $new_table_name = User::TABLE, array $fields = null): string
	{
		if (null === $fields) {
			$fields = $this->getCopyFields();
		}

		$db = DB::getInstance();

		return sprintf('INSERT INTO %s (%s) SELECT %s FROM %s;',
			$new_table_name,
			implode(', ', array_map([$db, 'quoteIdentifier'], $fields)),
			implode(', ', array_map([$db, 'quoteIdentifier'], array_keys($fields))),
			$old_table_name
		);
	}

	public function copy(string $old_table_name, string $new_table_name = User::TABLE, array $fields = null): void
	{
		DB::getInstance()->exec($this->getSQLCopy($old_table_name, $new_table_name, $fields));
	}

	public function create(string $table_name = User::TABLE)
	{
		$db = DB::getInstance();
		$db->begin();
		$this->createTable($table_name);
		$this->createIndexes($table_name);
		$db->commit();
	}

	public function createTable(string $table_name = User::TABLE): void
	{
		DB::getInstance()->exec($this->getSQLSchema($table_name));
	}

	public function createIndexes(string $table_name = User::TABLE): void
	{
		$db = DB::getInstance();
		$id_field = $db->firstColumn('SELECT value FROM config WHERE key = ?;', 'champ_identifiant');

		if ($id_field) {
			// Mettre les champs identifiant vides à NULL pour pouvoir créer un index unique
			$db->exec(sprintf('UPDATE %s SET %s = NULL WHERE %2$s = \'\';',
				$table_name, $id_field));

			$collation = '';

			if ($this->isText($id_field)) {
				$collation = ' COLLATE NOCASE';
			}

			// Création de l'index unique
			$db->exec(sprintf('CREATE UNIQUE INDEX IF NOT EXISTS users_id_field ON %s (%s%s);', $table_name, $id_field, $collation));
		}

		$db->exec(sprintf('CREATE UNIQUE INDEX IF NOT EXISTS user_number ON %s (numero);', $table_name));
		$db->exec(sprintf('CREATE INDEX IF NOT EXISTS users_category ON %s (id_category);', $table_name));

		// Create index on listed columns
		// FIXME: these indexes are currently unused by SQLite in the default user list
		// when there is more than one non-hidden category, as this makes SQLite merge multiple results
		// and so the index is not useful in that case sadly.
		// EXPLAIN QUERY PLAN SELECT * FROM membres WHERE "id_category" IN (3) ORDER BY "nom" ASC LIMIT 0,100;
		// --> SEARCH TABLE membres USING INDEX users_list_nom (id_category=?)
		// EXPLAIN QUERY PLAN SELECT * FROM membres WHERE "id_category" IN (3, 7) ORDER BY "nom" ASC LIMIT 0,100;
		// --> SEARCH TABLE membres USING INDEX user_category (id_category=?)
		// USE TEMP B-TREE FOR ORDER BY
		$listed_fields = array_keys((array) $this->getListedFields());
		foreach ($listed_fields as $field) {
			if ($field === $id_field) {
				// Il y a déjà un index
				continue;
			}

			$collation = '';

			if ($this->isText($field)) {
				$collation = ' COLLATE NOCASE';
			}

			$db->exec(sprintf('CREATE INDEX IF NOT EXISTS users_list_%s ON %s (id_category, %1$s%s);', $field, $table_name, $collation));
		}
	}

	/**
	 * Enregistre les changements de champs en base de données
	 * @return boolean true
	 */
	public function rebuildUsersTable()
	{
		$db = DB::getInstance();

		$db->exec('PRAGMA foreign_keys = OFF;');

		$db->begin();
		$this->createTable(User::TABLE . '_tmp');
		$this->copy(User::TABLE, User::TABLE . '_tmp');
		$db->exec(sprintf('DROP TABLE IF EXISTS %s;', User::TABLE));
		$db->exec(sprintf('ALTER TABLE %s_tmp RENAME TO %1$s;', User::TABLE));

		$this->createIndexes(User::TABLE);

		$db->commit();
		$db->exec('PRAGMA foreign_keys = ON;');

		return true;
	}

	public function preview(array $fields)
	{
	}

	public function save(array $fields)
	{
		foreach ($this->_fields_by_system_use as $key => $field) {
			if (!array_key_exists($key, $fields)) {
				throw new UserException(sprintf('Le champ "%s" ne peut être supprimé des fiches membres car il est utilisé dans la configuration.'));
			}
		}

		foreach ($fields as $field) {
			$field->save();
		}
	}
}
