<?php
declare(strict_types=1);

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

	protected array $_fields = [];
	protected array $_fields_by_type = [];
	protected array $_fields_by_system_use = [
		'login' => [],
		'password' => [],
		'name' => [],
		'number' => [],
	];

	protected array $_presets;

	protected array $_deleted = [];

	static protected $_instance;

	static public function getInstance(): self
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

	static public function getFirstEmailField(): string
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

	static public function getNameLabel(): string
	{
		$list = self::getInstance()->fieldsBySystemUse('name');
		$labels = [];

		foreach ($list as $field) {
			$labels[] = $field->label;
		}

		return implode(', ', $labels);
	}

	static public function getFirstNameField(): string
	{
		return key(self::getInstance()->fieldsBySystemUse('name'));
	}

	static public function getNameFieldsSQL(?string $prefix = null): string
	{
		$fields = self::getNameFields();
		$db= DB::getInstance();

		if ($prefix) {
			$fields = array_map(fn($v) => $prefix . '.' . $db->quoteIdentifier($v), $fields);
		}

		return implode(' || \' \' ', $fields);
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

		// First check that the field can be used as login
		$sql = sprintf('SELECT (COUNT(DISTINCT transliterate_to_ascii(%s)) = COUNT(*)) FROM users WHERE %1$s IS NOT NULL AND %1$s != \'\';', $new_field);

		if (!$db->firstColumn($sql)) {
			throw new UserException(sprintf('Le champ "%s" comporte des doublons et ne peut donc pas servir comme identifiant unique de connexion.', $new_field));
		}

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

	public function install(): void
	{
		$presets = $this->getDefaultPresets();

		foreach ($presets as $name => $preset) {
			$field = $this->addFieldFromPreset($name);

			if ($name == 'password') {
				$name = 'password';
				$field->system |= $field::PASSWORD;
			}

			if ($name == 'email') {
				$field->system |= $field::LOGIN;
			}

			if ($name == 'name') {
				$field->system |= $field::NAME;
			}

			if ($name == 'numero') {
				$field->system |= $field::NUMBER;
			}
		}

		$this->save();
	}

	public function addFieldFromPreset(string $name): DynamicField
	{
		$data = $this->getPresets()[$name];

		foreach ($data->depends ?? [] as $depends) {
			if (!$this->fieldByKey($depends)) {
				throw new \LogicException(sprintf('Cannot add "%s" preset if "%s" preset is not installed.', $name, $depends));
			}
		}

		$field = new DynamicField;
		$field->system |= $field::PRESET;
		$field->set('name', $name);
		$field->import((array)$data);
		$field->sort_order = $this->getLastOrderIndex();

		$this->add($field);

		return $field;
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

	public function fieldsByType(string $type): array
	{
		return $this->_fields_by_type[$type] ?? [];
	}

	public function fieldByKey(string $key): ?DynamicField
	{
		return $this->_fields[$key] ?? null;
	}

	public function fieldById(int $id): ?DynamicField
	{
		foreach ($this->_fields as $field) {
			if ($field->id === $id) {
				return $field;
			}
		}

		return null;
	}

	public function fieldsBySystemUse(string $use): array
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
		if (!isset($this->_presets))
		{
			$this->_presets = parse_ini_file(self::PRESETS_FILE, true);

			foreach ($this->_presets as &$preset) {
				$preset = (object) $preset;
			}

			unset($preset);
		}

		return $this->_presets;
	}

	/**
	 * Return list of presets that are not installed already
	 */
	public function getInstallablePresets(): array
	{
		$list = array_diff_key($this->getPresets(), $this->_fields);
		$installed =& $this->_fields;
		array_walk($list, function (&$p) use ($installed) {
			$p->disabled = false;

			foreach ($p->depends ?? [] as $d) {
				if (!array_key_exists($d, $installed)) {
					$p->disabled = true;
					break;
				}
			}
		});

		return $list;
	}

	public function getDefaultPresets(): array
	{
		return array_filter($this->getPresets(), fn ($row) => $row->default ?? false);
	}

	public function installPreset(string $name): DynamicField
	{
		$preset = $this->getInstallablePresets()[$name] ?? null;

		if (!$preset) {
			throw new \InvalidArgumentException('This field cannot be installed.');
		}

		return $this->addFieldFromPreset($name);
	}

	/**
	 * Import from old INI config
	 * @deprecated Only use when migrating from an old version
	 */
	static public function fromOldINI(string $config, string $login_field, string $name_field, string $number_field)
	{
		$db = DB::getInstance();
		$config = parse_ini_string($config, true);

		$presets = parse_ini_file(self::PRESETS_FILE, true);

		$i = 0;

		$self = new self(false);
		$fields = [
			'date_connexion'   => 'date_login',
			'date_inscription' => 'date_inscription',
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

			if ($data['type'] == 'checkbox' || $data['type'] == 'multiple') {
				// A checkbox/multiple checkbox can either be 0 or 1, not NULL
				$db->exec(sprintf('UPDATE membres SET %s = 0 WHERE %1$s IS NULL OR %1$s = \'\';', $name));
			}
			else {
				// Make sure data is NULL if empty
				$db->exec(sprintf('UPDATE membres SET %s = NULL WHERE %1$s = \'\';', $name));
			}

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
				$data['help'] = null;
				$data['mandatory'] = true;
				$data['editable'] = false;
			}

			$data = array_merge($defaults, $data);

			if (array_key_exists($name, $presets)) {
				$field->system = $field->system | $field::PRESET;
			}

			$field->set('name', $name);
			$field->set('label', $data['title']);
			$field->set('type', $data['type']);
			$field->set('help', empty($data['help']) ? null : $data['help']);
			$field->set('read_access', $data['private'] ? $field::ACCESS_ADMIN : $field::ACCESS_USER);
			$field->set('write_access', $data['editable'] ? $field::ACCESS_ADMIN : $field::ACCESS_USER);
			$field->set('required', (bool) $data['mandatory']);
			$field->set('list_table', (bool) $data['list_row']);
			$field->set('sort_order', $i++);
			$self->add($field);
		}

		// Create date_inscription
		$field = $self->addFieldFromPreset('date_inscription');
		$self->add($field);

		self::$_instance = $self;

		$self->createTable();
		$self->createIndexes();
		$self->createTriggers();
		$self->copy('membres', User::TABLE, $fields);

		$self->rebuildSearchTable(true);

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

				return empty($a->list_table) ? false : true;
			},
			ARRAY_FILTER_USE_BOTH
		);

		uasort($fields, function ($a, $b) {
			if ($a->sort_order == $b->sort_order)
				return 0;

			return ($a->sort_order > $b->sort_order) ? 1 : -1;
		});

		return $fields;
	}

	public function getSQLSchema(string $table_name = User::TABLE): string
	{
		$db = DB::getInstance();

		$create = DynamicField::SYSTEM_FIELDS_SQL;

		end($this->_fields);
		$last_one = key($this->_fields);

		foreach ($this->_fields as $key => $cfg)
		{
			$type = DynamicField::SQL_TYPES[$cfg->type];
			$line = sprintf('%s %s', $db->quoteIdentifier($key), $type);

			if ($type == 'GENERATED' && $cfg->sql) {
				$line .= sprintf(' ALWAYS AS (%s) VIRTUAL', $cfg->sql);
			}

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

	public function getSearchColumns(): array
	{
		$c = array_keys(array_filter($this->_fields, fn ($f) => $f->hasSearchCache()));
		return $c = array_combine($c, $c);
	}

	public function getSQLCopy(string $old_table_name, string $new_table_name = User::TABLE, array $fields = null, string $function = null): string
	{
		$db = DB::getInstance();
		unset($fields['id']);
		$source = array_map([$db, 'quoteIdentifier'], array_keys($fields));

		if ($function) {
			$source = array_map(fn($a) => $function . '(' . $a . ')', $source);
		}

		return sprintf('INSERT INTO %s (id, %s) SELECT id, %s FROM %s;',
			$new_table_name,
			implode(', ', array_map([$db, 'quoteIdentifier'], $fields)),
			implode(', ', $source),
			$old_table_name
		);
	}

	public function copy(string $old_table_name, string $new_table_name = User::TABLE, ?array $fields = null): void
	{
		$sql = $this->getSQLCopy($old_table_name, $new_table_name, $fields);
		DB::getInstance()->exec($sql);
	}

	public function createTable(string $table_name = User::TABLE): void
	{
		$db = DB::getInstance();
		$schema = $this->getSQLSchema($table_name);
		$db->exec($schema);
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
		$db->exec(sprintf('CREATE INDEX IF NOT EXISTS users_parent ON %s (id_parent);', $table_name));
		$db->exec(sprintf('CREATE INDEX IF NOT EXISTS users_is_parent ON %s (is_parent);', $table_name));
	}

	public function createTriggers(string $table_name = User::TABLE): void
	{
		// These triggers are to set id_parent to the ID of the user on parent user, when the user has children
		$db = DB::getInstance();
		$db->exec(sprintf('
			CREATE TRIGGER %1$s_parent_trigger_update_new AFTER UPDATE OF id_parent ON %1$s BEGIN
				UPDATE users SET is_parent = 1 WHERE id = NEW.id_parent;
			END;
			CREATE TRIGGER %1$s_parent_trigger_update_old AFTER UPDATE OF id_parent ON %1$s BEGIN
				-- Set is_parent to 0 if user has no longer any children
				UPDATE %1$s SET is_parent = 0 WHERE id = OLD.id_parent
					AND 0 = (SELECT COUNT(*) FROM %1$s WHERE id_parent = OLD.id_parent);
			END;
			CREATE TRIGGER %1$s_parent_trigger_insert AFTER INSERT ON %1$s BEGIN
				SELECT CASE WHEN NEW.id_parent IS NULL THEN RAISE(IGNORE) ELSE 0 END;
				UPDATE users SET is_parent = 1 WHERE id = NEW.id_parent;
			END;
			CREATE TRIGGER %1$s_parent_trigger_delete AFTER DELETE ON %1$s BEGIN
				SELECT CASE WHEN OLD.id_parent IS NULL THEN RAISE(IGNORE) ELSE 0 END;
				-- Set is_parent to 0 if user has no longer any children
				UPDATE %1$s SET is_parent = 0 WHERE id = OLD.id_parent
					AND 0 = (SELECT COUNT(*) FROM users WHERE id_parent = OLD.id_parent);
			END;', $table_name));
	}

	/**
	 * Enregistre les changements de champs en base de données
	 */
	public function rebuildUsersTable(array $fields): void
	{
		$db = DB::getInstance();

		$fields = array_combine($fields, $fields);

		// Generated fields cannot be copied
		foreach ($this->_fields as $f) {
			if (DynamicField::SQL_TYPES[$f->type] == 'GENERATED') {
				unset($fields[$f->name]);
			}
		}

		// Always copy system fields
		$system_fields = array_keys(DynamicField::SYSTEM_FIELDS);
		$fields = array_merge(array_combine($system_fields, $system_fields), $fields);

		$in_transaction = $db->inTransaction();

		if (!$in_transaction) {
			$db->beginSchemaUpdate();
		}

		$this->createTable(User::TABLE . '_tmp');
		$this->copy(User::TABLE, User::TABLE . '_tmp', $fields);
		$db->exec(sprintf('DROP TABLE IF EXISTS %s;', User::TABLE));
		$db->exec(sprintf('ALTER TABLE %s_tmp RENAME TO %1$s;', User::TABLE));

		$this->createIndexes(User::TABLE);
		$this->createTriggers(User::TABLE);

		$this->rebuildSearchTable(false);

		if (!$in_transaction) {
			$db->commitSchemaUpdate();
		}
	}

	public function rebuildSearchTable(bool $from_users_table = true): void
	{
		$db = DB::getInstance();
		$db->begin();

		$search_table = User::TABLE . '_search';
		$columns = $this->getSearchColumns();

		$sql = sprintf("CREATE TABLE IF NOT EXISTS %s\n(\n\tid INTEGER PRIMARY KEY NOT NULL REFERENCES %s (id) ON DELETE CASCADE,\n\t%s\n);", $search_table . '_tmp', User::TABLE, implode(",\n\t", $columns));

		$db->exec($sql);

		if ($from_users_table) {
			// This is slower but is necessary sometimes
			$sql = $this->getSQLCopy(User::TABLE, $search_table . '_tmp', $columns, 'transliterate_to_ascii');
		}
		else {
			$sql = $this->getSQLCopy($search_table, $search_table . '_tmp', $columns);
		}

		$db->exec($sql);
		$db->exec(sprintf('DROP TABLE IF EXISTS %s;', $search_table));
		$db->exec(sprintf('ALTER TABLE %s_tmp RENAME TO %1$s;', $search_table));

		foreach ($columns as $column) {
			$sql = sprintf("CREATE INDEX IF NOT EXISTS %s ON %s (%s);\n",
				$db->quoteIdentifier($search_table . '_' . $column),
				$search_table,
				$db->quoteIdentifier($column)
			);

			$db->exec($sql);
		}

		$db->commit();
	}

	public function rebuildUserSearchCache(int $id): void
	{
		$db = DB::getInstance();
		$columns = $this->getSearchColumns();
		$keys = array_map([$db, 'quoteIdentifier'], $columns);
		$copy = array_map(fn($c) => sprintf('transliterate_to_ascii(%s)', $c), $keys);

		$sql = sprintf('INSERT OR REPLACE INTO %s_search (id, %s) SELECT id, %s FROM %1$s WHERE id = %d;',
			User::TABLE,
			implode(', ', $keys),
			implode(', ', $copy),
			$id
		);

		$db->exec($sql);
	}

	public function add(DynamicField $df)
	{
		$this->_fields[$df->name] = $df;
		$this->reloadCache();
	}

	public function delete(string $name)
	{
		$this->_deleted[] = $this->_fields[$name];
		unset($this->_fields[$name]);

		$this->reloadCache();
	}

	public function save(bool $allow_rebuild = true)
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

		$rebuild = false;

		$db = DB::getInstance();

		// We need to disable foreign keys BEFORE we start the transaction
		// this means that the config_users_fields table CANNOT have any foreign keys
		$db->beginSchemaUpdate();

		$copy = [];

		foreach ($this->_fields as $field) {
			if (!$field->exists()) {
				$rebuild = true;
			}
			else {
				$copy[] = $field->name;
			}

			if ($field->isModified()) {
				$field->save();
			}
		}

		foreach ($this->_deleted as $f) {
			$f->delete();
			$rebuild = true;
		}

		$this->_deleted = [];

		if ($rebuild && $allow_rebuild) {
			// FIXME/TODO: use ALTER TABLE ... DROP COLUMN for SQLite 3.35.0+
			// some conditions apply
			// https://www.sqlite.org/lang_altertable.html#altertabdropcol
			$this->rebuildUsersTable($copy);
		}

		$db->commitSchemaUpdate();

		$this->reload();
	}

	public function setOrderAll(array $order)
	{
		foreach (array_values($order) as $sort => $key) {
			if (!array_key_exists($key, $this->_fields)) {
				throw new \InvalidArgumentException('Unknown field name: ' . $key);
			}

			$this->_fields[$key]->set('sort_order', $sort);
		}
	}

	public function getLastOrderIndex()
	{
		return count($this->_fields);
	}
}
