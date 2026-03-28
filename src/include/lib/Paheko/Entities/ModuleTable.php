<?php

declare(strict_types = 1);

namespace Paheko\Entities;

use Paheko\Entity;
use Paheko\DB;

use Paheko\UserTemplate\Modules;

use stdClass;

class ModuleTable extends Entity
{
	const TABLE = 'modules_tables';

	protected ?int $id;
	protected ?int $id_module;

	protected string $name;
	protected ?string $comment;

	protected array $columns;

	protected array $_renamed_columns = [];
	protected ?Module $_module;

	const DEFAULT_COLUMNS = [
		'id' => 'INTEGER NOT NULL,',
		'key' => 'TEXT NOT NULL,',
	];

	const DEFAULT_SUFFIX = 'UNIQUE (key), PRIMARY KEY (id)';

	const COLUMN_DEFINITION_REGEXP = '/^(TEXT|INT|INTEGER|DATETIME|REAL|FLOAT|NUMERIC)
			(?:\s+(NOT\s+NULL|NULL))?
			(?:\s+DEFAULT\s+("[^"]*"|\'[^\']*\'|\d+|CURRENT_TIMESTAMP))?
			(?:\s+REFERENCES\s+((?-i)!?[a-z0-9_]+)\s*\(((?-i)[a-z0-9_]+)\)(?:\s+ON\s+DELETE\s+(SET\s+NULL|RESTRICT|CASCADE))?)?
			(\s+UNIQUE(?:\s+\((?-i)[a-z0-9_]+\))?)?
			(?:\s+COMMENT\s+("[^"]*"|\'[^\']*\'))?$/xi';

	const COLUMN_TYPES = [
		'TEXT',
		'INTEGER',
		'DATETIME',
		'REAL',
		'NUMERIC',
	];


	public function selfCheck(): void
	{
		$this->assert(preg_match(Module::TABLE_NAME_REGEXP, $this->name), 'The table name is invalid: ' . $this->name);

		if (isset($this->comment)) {
			$this->assert(strlen($this->comment) <= 70, 'The table comment cannot be longer than 70 characters');
			$this->assert(!str_contains($this->comment, "\n") && !str_contains($this->comment, "\r"), 'The table comment cannot contain line breaks');
		}

		$db = DB::getInstance();

		if ($this->exists() && $this->isModified('name')) {
			$this->assert(!$db->test(self::TABLE, 'name = ? AND id != ?', $this->getRealName($this->name), $this->id()), 'This table name is already used: ' . $this->name);
		}
		elseif (!$this->exists()) {
			$this->assert(!$db->test(self::TABLE, 'name = ?', $this->getRealName($this->name)), 'This table name is already used: ' . $this->name);
		}

		$this->assert(count($this->columns), 'This table has no columns');

		parent::selfCheck();
	}

	public function renameColumn(string $old_name, string $new_name): void
	{
		if (!array_key_exists($old_name, $this->columns)) {
			throw new \InvalidArgumentException('Cannot rename this column as it doesn\'t exist: ' . $old_name);
		}

		if (array_key_exists($new_name, $this->columns)) {
			throw new \InvalidArgumentException('Cannot rename, this column already exists: ' . $new_name);
		}

		if (array_key_exists($old_name, self::DEFAULT_COLUMNS)) {
			throw new \InvalidArgumentException('Cannot modify a default table column: ' . $name);
		}

		if (array_key_exists($new_name, self::DEFAULT_COLUMNS)) {
			throw new \InvalidArgumentException('Column name is already used by a default table column: ' . $name);
		}


		$this->_renamed_columns[$old_name] = $new_name;
		$this->columns[$new_name] = $this->columns[$old_name];
		unset($this->columns[$old_name]);
	}

	public function dropColumn(string $name): void
	{
		if (!array_key_exists($name, $this->columns)) {
			throw new \InvalidArgumentException('Cannot delete this column as it doesn\'t exist: ' . $name);
		}

		if (array_key_exists($name, self::DEFAULT_COLUMNS)) {
			throw new \InvalidArgumentException('Cannot delete a default table column: ' . $name);
		}

		$columns = $this->columns;
		unset($columns[$name]);
		$this->set('columns', $columns);
	}

	public function addColumn(string $name, array|string $definition): array
	{
		if (array_key_exists($name, $this->columns)) {
			throw new \InvalidArgumentException('Column name already exists: ' . $name);
		}

		if (array_key_exists($name, self::DEFAULT_COLUMNS)) {
			throw new \InvalidArgumentException('Column name is already used by a default table column: ' . $name);
		}

		$columns = $this->columns;
		$columns[$name] = $this->parseColumnDefinition($name, $definition);
		$this->set('columns', $columns);
		return (array) $columns[$name];
	}

	public function setColumns(array $columns): void
	{
		$this->columns = [];

		foreach ($columns as $name => $value) {
			if (array_key_exists($name, $this->columns)) {
				throw new \InvalidArgumentException('Column name already exists: ' . $name);
			}

			if (array_key_exists($name, self::DEFAULT_COLUMNS)) {
				throw new \InvalidArgumentException('Column name is already used by a default table column: ' . $name);
			}

			$this->columns[$name] = $this->parseColumnDefinition($name, $value);
		}
	}

	public function modifyColumn(string $name, array|string $definition): array
	{
		if (!array_key_exists($name, $this->columns)) {
			throw new \InvalidArgumentException('Cannot modify this column as it doesn\'t exist: ' . $name);
		}

		if (array_key_exists($name, self::DEFAULT_COLUMNS)) {
			throw new \InvalidArgumentException('Cannot modify a default table column: ' . $name);
		}

		$columns = $this->columns;
		$columns[$name] = $this->parseColumnDefinition($name, $definition);
		$this->set('columns', $columns);
		return (array) $columns[$name];
	}

	public function getRealName(?string $name = null, bool $allow_external_table = false): string
	{
		if ($allow_external_table
			&& $name[0] === '!') {
			return substr($name, 1);
		}

		return Modules::getModuleTableName($this->module()->name, $name ?? $this->name);
	}

	public function delete(): bool
	{
		$db = DB::getInstance();
		$db->begin();

		$sql = sprintf('DROP TABLE IF EXISTS %s;', $db->quoteIdentifier($table_name));
		$db->exec($sql);

		$r = parent::delete();
		$db->commit();
		return $r;
	}

	public function save(bool $selfcheck = true): bool
	{
		$db = DB::getInstance();
		$db->begin();

		$modified = $this->getModifiedProperties();
		$exists = $this->exists();

		$r = parent::save($selfcheck);

		if (!$r) {
			$db->rollback();
			return $r;
		}

		$sql = [];

		$table_name = $this->getRealName();

		// Just rename table
		if ($exists
			&& array_key_exists('name', $modified)) {
			$old_name = Modules::getModuleTableName($this->module()->name, $modified['name']);
			$sql[] = sprintf('ALTER TABLE %s RENAME TO %s;', $db->quoteIdentifier($old_name), $db->quoteIdentifier($table_name));
		}

		// Rename columns
		foreach ($this->_renamed_columns as $old_name => $new_name) {
			$sql[] = sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s;',
				$db->quoteIdentifier($table_name),
				$db->quoteIdentifier($old_name),
				$db->quoteIdentifier($new_name)
			);
		}

		// Re-create schema if columns have been modified
		if (!$exists
			|| array_key_exists('comment', $modified)
			|| array_key_exists('columns', $modified)) {
			if ($exists) {
				$sql[] = sprintf('ALTER TABLE %s RENAME TO %s;', $db->quoteIdentifier($table_name), $db->quoteIdentifier($table_name . '_old'));
			}

			$sql[] = $this->getSQL();

			if ($exists) {
				$columns_names = array_keys($this->columns);
				$columns_names = array_map([$db, 'quoteIdentifier'], $columns_names);
				$columns_names = implode(', ', $columns_names);

				$sql[] = sprintf('INSERT INTO %s (%s) SELECT %2$s FROM %s;',
					$db->quoteIdentifier($table_name),
					$columns_names,
					$db->quoteIdentifier($table_name . '_old')
				);

				$sql[] = sprintf('DROP TABLE %s;', $db->quoteIdentifier($table_name . '_old'));
			}
		}

		// set authorizer to only allow working on this specific table
		$db->enableTableAuthorizer($table_name);

		foreach ($sql as $line) {
			$db->exec($line);
		}

		$this->_renamed_columns = [];

		$db->commit();

		// Re-enable default authorizer
		$db->enableSafetyAuthorizer();
		return $r;
	}

	public function getSQL(): string
	{
		$comment = '';

		if (isset($this->comment)) {
			$comment = '-- ' . $this->comment . "\n";
		}

		$columns_str = [];

		foreach (self::DEFAULT_COLUMNS as $key => $definition) {
			$columns_str[] = $key . ' ' . $definition;
		}

		foreach ($this->columns as $name => $definition) {
			$columns_str[] = $this->getColumnSQL($name, (object) $definition);
		}

		$columns_str = implode("\n  ", $columns_str);

		// putting the primary key definition here instead of in the column definition
		// is better as it avoids having to delete the comma from the last column definition
		$columns_str .= "\n  " . self::DEFAULT_SUFFIX;

		$db = DB::getInstance();
		$table = $this->getRealName();

		$sql = sprintf("CREATE TABLE IF NOT EXISTS %s\n%s(\n  %s\n);",
			$db->quoteIdentifier($table),
			$comment,
			$columns_str
		);

		return $sql;
	}

	/**
	 * Verify column definition and export it to SQL code
	 */
	protected function parseColumnDefinition(string $name, string|array $definition): stdClass
	{
		if (!preg_match(Module::TABLE_NAME_REGEXP, $name)) {
			throw new \InvalidArgumentException('Invalid column name: ' . $name);
		}

		if (is_string($definition)) {
			if (!preg_match(self::COLUMN_DEFINITION_REGEXP, $definition, $match)) {
				throw new \InvalidArgumentException(sprintf('Invalid column "%s" definition: %s', $name, $definition));
			}

			$definition = (object) [
				'type'         => $match[1],
				'null'         => str_contains(strtoupper($match[2] ?? ''), 'NOT NULL') ? false : true,
				'default'      => isset($match[3]) && $match[3] !== '' ? $match[3] : null,
				'fk_table'     => !empty($match[4]) ? trim($match[4], '\'" ') : null,
				'fk_column'    => !empty($match[5]) ? trim($match[5], '\'" ') : null,
				'fk_on_delete' => !empty($match[6]) ? $match[6] : null,
				'unique'       => !empty($match[8]) ? $match[8] : (!empty($match[7]) ? true : null),
				'comment'      => !empty($match[11]) ? trim($match[11], '\'"') : null,
			];
		}
		elseif (is_array($definition)) {
			static $keys = null;

			if (null === $keys) {
				$keys = ['type', 'null', 'default', 'fk_table', 'fk_column', 'fk_on_delete', 'unique', 'comment'];
				$keys = array_flip($keys);
			}

			$definition = (object) array_intersect_key($definition, $keys);
		}
		else {
			throw new \InvalidArgumentException('Invalid column definition for: ' . $name);
		}

		$db = DB::getInstance();
		$definition->name = $name;

		if (empty($definition->type)) {
			throw new \InvalidArgumentException('Missing type for column: ' . $name);
		}

		$definition->type = strtoupper($definition->type);
		$definition->fk_on_delete = isset($definition->fk_on_delete) ? strtoupper($definition->fk_on_delete) : null;
		$definition->constraint = null;

		if ($definition->type === 'INT') {
			$definition->type === 'INTEGER';
		}
		elseif ($definition->type === 'FLOAT') {
			$definition->type === 'REAL';
		}
		elseif ($definition->type === 'DATETIME') {
			// Keep DATETIME as type, SQLite recognizes this as TEXT
			$definition->constraint = sprintf('CHECK (%s IS NULL OR datetime(%1$s) = %1$s)', $db->quoteIdentifier($name));
		}

		if (!in_array($definition->type, self::COLUMN_TYPES, true)) {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s": unknown type "%s"', $definition->type));
		}
		elseif (!is_bool($definition->null)) {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s": null can only be a boolean', $name));
		}
		elseif (isset($definition->default)
			&& strtoupper($definition->default) === 'CURRENT_TIMESTAMP'
			&& $definition->type !== 'DATETIME') {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s": default value "%s" is only valid for DATETIME type', $name, $definition->default));
		}
		elseif (isset($definition->fk_on_delete)
			&& !in_array($definition->fk_on_delete, ['SET NULL', 'RESTRICT', 'CASCADE'], true)) {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s": unknown foreign key constraint "%s"', $name, $definition->fk_on_delete));
		}
		elseif (isset($definition->fk_table)
			&& !isset($definition->fk_column)) {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s" foreign key: table is defined, but no column is defined', $name));
		}
		elseif (isset($definition->fk_table)
			&& !isset($definition->fk_column)) {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s" foreign key: table is defined, but no column is defined', $name));
		}
		elseif (isset($definition->fk_table)
			&& !preg_match('/^!?[a-z]+(?:_[a-z]+)*$/', $definition->fk_table)) {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s" foreign key: invalid table name "%s"', $name, $definition->fk_table));
		}
		elseif (isset($definition->fk_column)
			&& !preg_match(Module::TABLE_NAME_REGEXP, $definition->fk_column)) {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s" foreign key column name "%s"', $name, $definition->fk_column));
		}
		elseif (isset($definition->fk_on_delete)
			&& strtoupper($definition->fk_on_delete) === 'RESTRICT'
			&& substr($definition->fk_table, 0, 1) === '!') {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s": foreign key constraint "%s" is only valid for internal module tables', $name, $definition->fk_on_delete));
		}
		elseif (isset($definition->unique)
			&& $definition->unique !== true
			&& !preg_match(Module::TABLE_NAME_REGEXP, $definition->unique)) {
			throw new \InvalidArgumentException(sprintf('Invalid column "%s": invalid unique index name "%s"', $name, $definition->unique));
		}

		if (isset($definition->fk_table)) {
			$fk_table = $this->getRealName($definition->fk_table, true);

			// Validate foreign key table and column exists
			static $fk_tables = [];

			$fk_tables[$fk_table] ??= $db->getTableSchema($fk_table);

			if (!$fk_tables[$fk_table]) {
				throw new \InvalidArgumentException(sprintf('Invalid foreign key: "%s" table does not exist', $definition->fk_table));
			}
			elseif (!array_key_exists($definition->fk_column, $fk_tables[$fk_table]['columns'])) {
				throw new \InvalidArgumentException(sprintf('Invalid foreign key: "%s" column does not exist in "%s" table', $definition->fk_column, $definition->fk_table));
			}
			// apparently sqlite doesn't care about matching foreign key column type and local column type, that's good
		}

		return $definition;
	}

	public function getColumnSQL(string $name, stdClass $column): string
	{
		$db = DB::getInstance();
		$sql = sprintf('%s %s %s', $db->quoteIdentifier($name), $column->type, $column->null ? 'NULL' : 'NOT NULL');

		if (isset($column->default)) {
			$sql .= ' DEFAULT ';

			if (strtoupper($column->default) === 'CURRENT_TIMESTAMP'
				|| ctype_digit($column->default)) {
				$sql .= $column->default;
			}
			else {
				$sql .= $db->quote($column->default);
			}
		}

		if (isset($column->fk_table)) {
			$fk_table = $this->getRealName($column->fk_table, true);

			$sql .= sprintf(' REFERENCES %s (%s) ON DELETE %s',
				$db->quoteIdentifier($fk_table),
				$db->quoteIdentifier($column->fk_column),
				$column->fk_on_delete ?? 'SET NULL'
			);
		}

		if ($column->unique === true) {
			$sql .= ' UNIQUE';
		}

		$sql .= ',';

		if (isset($column->comment)) {
			// Make sure the user cannot escape comment
			$comment = preg_replace('/[^a-zA-Z0-9_\p{L}]+/u', ' ', $column->comment);
			$comment = mb_substr($comment, 0, 150);

			if (trim($comment) !== '') {
				$sql .= ' -- ' . $comment;
			}
		}

		return $sql;
	}

	public function getSize(): int
	{
		return DB::getInstance()->getTableSize($this->getRealName());
	}

	public function countRows(): int
	{
		return DB::getInstance()->count($this->getRealName());
	}

	public function setModule(Module $module): void
	{
		$this->_module = $module;
	}

	public function module(): Module
	{
		return $this->_module;
	}
}
