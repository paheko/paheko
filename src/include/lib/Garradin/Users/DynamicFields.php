<?php

namespace Garradin\Users;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\ValidationException;

use Garradin\Entities\Users\DynamicField;
use Garradin\Entities\Users\User;

use KD2\DB\EntityManager as EM;

use const Garradin\ROOT;

class DynamicFields
{
	const PRESETS_FILE = ROOT . '/include/data/users_fields_presets.ini';

	const TABLE = DynamicField::TABLE;

	protected $_fields = [];
	protected $_fields_by_type = [];
	protected $_fields_by_system_use = [
		'login' => [],
		'password' => [],
		'name' => [],
		'number' => [],
	];

	protected $_presets = [];

	static protected $_instance;

	static public function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	static public function get(string $key)
	{
		return self::getInstance()->fieldByKey($key);
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

	/**
	 * FIXME use generated columns instead https://www.sqlite.org/gencol.html
	 */
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
		$i = EM::getInstance(DynamicField::class)->iterate('SELECT * FROM @TABLE ORDER BY sort_order;');

		foreach ($i as $field) {
			$this->_fields[$field->name] = $field;
		}

		$this->reloadCache();
	}

	protected function reloadCache()
	{
		$this->_fields_by_type = [];

		foreach ($this->_fields_by_system_use as &$list) {
			$list = [];
		}
		unset($list);

		foreach ($this->_fields as $key => $field) {
			if (!isset($this->_fields_by_type[$field->type])) {
				$this->_fields_by_type[$field->type] = [];
			}

			$this->_fields_by_type[$field->type][$key] = $field;

			if (!$field->system) {
				continue;
			}

			if ($field->system & $field::PASSWORD) {
				$this->_fields_by_system_use['password'][$key] = $field;
			}

			if ($field->system & $field::NAME) {
				$this->_fields_by_system_use['name'][$key] = $field;
			}

			if ($field->system & $field::NUMBER) {
				$this->_fields_by_system_use['number'][$key] = $field;
			}

			if ($field->system & $field::LOGIN) {
				$this->_fields_by_system_use['login'][$key] = $field;
			}
		}
	}

	protected function fieldsByType(string $type): array
	{
		return $this->_fields_by_type[$type] ?? [];
	}

	protected function fieldByKey(string $key): ?DynamicField
	{
		return $this->_fields[$key] ?? null;
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
	 * @deprecated Only use when migrating from an old version
	 */
	static public function fromOldINI(string $config, string $login_field, string $name_field, string $number_field)
	{
		$db = DB::getInstance();
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
				$field->system |= $field::PASSWORD;
				$fields['passe'] = 'password';
			}

			if ($name == $login_field) {
				$field->system |= $field::LOGIN;
			}

			if ($name == $name_field) {
				$field->system |= $field::NAME;
			}

			if ($name == $number_field) {
				$field->system |= $field::NUMBER;
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
			$self->add($field);

			if ($field->type == 'checkbox' || $field->type == 'multiple') {
				// A checkbox/multiple checkbox can either be 0 or 1, not NULL
				$db->exec(sprintf('UPDATE membres SET %s = 0 WHERE %1$s IS NULL;', $field->name));
			}
		}

		self::$_instance = $self;

		$self->createTable();
		$self->createIndexes();
		$self->copy('membres', User::TABLE, $fields);

		return $self;
	}

	public function isText(string $field)
	{
		$type = $this->_fields[$field]->type;
		return DynamicField::SQL_TYPES[$type] == 'TEXT';
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
			return !($a->system & DynamicField::PASSWORD);
		});
	}

	public function listAssocNames()
	{
		$out = [];

		foreach ($this->_fields as $key => $field) {
			if ($field->system & $field::PASSWORD) {
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

	public function getListedFields(): array
	{
		$name_fields = self::getNameFields();
		$name_fields[] = self::getNumberField();

		$fields = array_filter(
			$this->_fields,
			function ($a, $b) use ($name_fields) {
				if (in_array($b, $name_fields)) {
					return false;
				}

				return empty($a->list_row) ? false : true;
			},
			ARRAY_FILTER_USE_BOTH
		);

		uasort($fields, function ($a, $b) {
			if ($a->list_row == $b->list_row)
				return 0;

			return ($a->list_row > $b->list_row) ? 1 : -1;
		});

		return $fields;
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
				$line .= ' COLLATE NOCASE';
			}

			if ($last_one != $key) {
				$line .= ',';
			}

			if (!empty($cfg->label))
			{
				$line .= ' -- ' . str_replace(["\n", "\r"], '', $cfg->label);
			}

			$create[] = $line;
		}

		$sql = sprintf("CREATE TABLE %s\n(\n\t%s\n);", $table_name, implode("\n\t", $create));
		return $sql;
	}

	/**
	 * Returns the SQL query used to create the search table and triggers
	 * This table is useful to make LIKE searches on unicode columns
	 */
	public function getSQLSearchSchema(string $table_name = User::TABLE): ?string
	{
		$search_table = $table_name . '_search';

		$columns = [];

		foreach ($this->_fields as $key => $cfg) {
			if ($cfg->type == 'text' || $cfg->list_row) {
				$columns[] = $key;
			}
		}

		if (!count($columns)) {
			return null;
		}

		$new_columns = array_map(fn ($v) => sprintf('NEW.%s', $v), $columns);

		$sql = sprintf("CREATE VIRTUAL TABLE IF NOT EXISTS %s USING fts4\n(\n\tcontent=%s,\n\ttokenize = unicode61 \"remove_diacritics=2\",\n\t%s\n);", $search_table, $table_name, implode(",\n\t", $columns));
		$sql .= "\n";

		// Triggers
		$sql .= sprintf("CREATE TRIGGER IF NOT EXISTS %s_ai AFTER INSERT ON %s BEGIN\n\t", $search_table, $table_name);
		$sql .= sprintf("INSERT INTO %s (docid, %s) VALUES (NEW.rowid, %s);\n", $search_table, implode(', ', $columns), implode(', ', $new_columns));
		$sql .= "END;\n";
		$sql .= sprintf("CREATE TRIGGER IF NOT EXISTS %s_au AFTER UPDATE ON %s BEGIN\n\t", $search_table, $table_name);
		$sql .= sprintf("INSERT INTO %s (docid, %s) VALUES (NEW.rowid, %s);\n", $search_table, implode(', ', $columns), implode(', ', $new_columns));
		$sql .= "END;\n";
		$sql .= sprintf("\nCREATE TRIGGER IF NOT EXISTS %s_bu BEFORE UPDATE ON %s BEGIN\n\t", $search_table, $table_name);
		$sql .= sprintf("DELETE FROM %s WHERE docid = OLD.rowid;\n", $search_table);
		$sql .= "END;\n";
		$sql .= sprintf("\nCREATE TRIGGER IF NOT EXISTS %s_bd BEFORE DELETE ON %s BEGIN\n\t", $search_table, $table_name);
		$sql .= sprintf("DELETE FROM %s WHERE docid = OLD.rowid;\n", $search_table);
		$sql .= "END;\n";

		return $sql;
	}

	public function getCopyFields(): array
	{
		// Champs à recopier
		$copy = array_keys(DynamicField::SYSTEM_FIELDS) + array_keys($this->_fields);
		return $copy;
	}

	public function getSQLCopy(string $old_table_name, string $new_table_name = User::TABLE, array $fields = null): string
	{
		if (null === $fields) {
			$fields = $this->getCopyFields();
		}

		$db = DB::getInstance();

		return sprintf('INSERT INTO %s (id, %s) SELECT id, %s FROM %s;',
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
		$db = DB::getInstance();
		$schema = $this->getSQLSchema($table_name);
		$db->exec($schema);

		$schema = $this->getSQLSearchSchema($table_name);

		if ($schema) {
			$db->exec($schema);
		}
	}

	public function createIndexes(string $table_name = User::TABLE): void
	{
		$id_field = null;
		$db = DB::getInstance();

		if ($id_field = $this->getLoginField()) {
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

		$db->exec(sprintf('CREATE UNIQUE INDEX IF NOT EXISTS users_number ON %s (%s);', $table_name, $this->getNumberField()));
		$db->exec(sprintf('CREATE INDEX IF NOT EXISTS users_category ON %s (id_category);', $table_name));
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

	public function add(DynamicField $df)
	{
		$this->_fields[$df->name] = $df;
		$this->reloadCache();
	}

	public function delete(string $name)
	{
		unset($this->_fields[$name]);
		$this->reloadCache();
	}

	public function save()
	{
		if (empty($this->_fields_by_system_use['number'])) {
			throw new ValidationException('Aucun champ de numéro de membre n\'existe');
		}

		if (count($this->_fields_by_system_use['number']) != 1) {
			throw new ValidationException('Un seul champ peut être défini comme numéro');
		}

		if (empty($this->_fields_by_system_use['name'])) {
			throw new ValidationException('Aucun champ de nom de membre n\'existe');
		}

		if (empty($this->_fields_by_system_use['login'])) {
			throw new ValidationException('Aucun champ d\'identifiant de connexion n\'existe');
		}

		if (count($this->_fields_by_system_use['login']) != 1) {
			throw new ValidationException('Un seul champ peut être défini comme identifiant');
		}

		if (empty($this->_fields_by_system_use['password'])) {
			throw new ValidationException('Aucun champ de mot de passe n\'existe');
		}

		if (count($this->_fields_by_system_use['password']) != 1) {
			throw new ValidationException('Un seul champ peut être défini comme mot de passe');
		}

		foreach ($this->_fields as $field) {
			$field->save();
		}
	}
}
