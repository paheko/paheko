<?php

namespace Paheko\Entities;

use Paheko\Entity;
use Paheko\DB;

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

	public function selfCheck(): void
	{
		$this->assert(preg_match(Module::TABLE_NAME_REGEXP, $this->name), 'The table name is invalid: ' . $this->name);
		$this->assert(is_null($this->comment) || strlen($this->comment) <= 70, 'The table comment cannot be longer than 70 characters');

		if ($this->exists() && $this->isModified('name')) {
			$this->assert(!$db->test(self::TABLE, 'name = ? AND id != ?', $this->getRealName($this->name), $this->id()), 'This table name is already used: ' . $this->name);
		}
		elseif (!$this->exists()) {
			$this->assert(!$db->test(self::TABLE, 'name = ?', $this->getRealName($this->name), $this->id()), 'This table name is already used: ' . $this->name);
		}

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

		$this->_renamed_columns[$old_name] = $new_name;
		$this->columns[$new_name] = $this->columns[$old_name];
		unset($this->columns[$old_name]);
	}

	public function dropColumn(string $name): void
	{
		if (!array_key_exists($name, $this->columns)) {
			throw new \InvalidArgumentException('Cannot delete this column as it doesn\'t exist: ' . $name);
		}

		$columns = $this->columns;
		unset($columns[$name]);
		$this->set('columns', $columns);
	}

	public function addColumn(string $name, ?string $comment, array|string $definition): stdClass
	{
		if (array_key_exists($name, $this->columns)) {
			throw new \InvalidArgumentException('Cannot add this column as it already exists: ' . $name);
		}

		$columns = $this->columns;
		$columns[$name] = $this->getColumnDefinition($name, $comment, $definition);
		$this->set('columns', $columns);
		return $columns[$name];
	}

	public function modifyColumn(string $name, ?string $comment, array|string $definition): stdClass
	{
		if (!array_key_exists($name, $this->columns)) {
			throw new \InvalidArgumentException('Cannot modify this column as it doesn\'t exist: ' . $name);
		}

		$columns = $this->columns;
		$columns[$name] = $this->getColumnDefinition($name, $comment, $definition);
		$this->set('columns', $columns);
		return $columns[$name];
	}

	public function getRealName(?string $name = null): string
	{
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
				$columns_names = array_map([$db, 'quoteIdenfier'], $columns_names);

				$sql[] = sprintf('INSERT INTO %s (%s) SELECT %s FROM %2$s;',
					$db->quoteIdentifier($table_name),
					$columns_names,
					$db->quoteIdentifier($table_name . '_old')
				);

				$sql[] = sprintf('DROP TABLE %s;', $db->quoteIdentifier($table_name . '_old'));
			}
		}

		// set authorizer to only allow working on this specific table
		$db->enableTableAuthorizer($table);

		foreach ($sql as $line) {
			$db->exec($line);
		}

		$this->_renamed_columns = [];

		$db->commit();

		// Re-enable default authorizer
		$db->enableSafetyAuthorizer();
		return $r;
	}


	/**
	 * Verify column definition and export it to SQL code
	 */
	protected function getColumnDefinition(string $name, string|array $definition): stdClass
	{
		if (!preg_match(Module::TABLE_NAME_REGEXP, $name)) {
			throw new TemplateException('Invalid column name: ' . $name);
		}

		if (is_string($definition)) {
			if (!preg_match(self::MODULE_COLUMN_DEFINITION_REGEXP, $definition, $match)) {
				throw new TemplateException(sprintf('Invalid column "%s" definition: %s', $name, $definition));
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
			throw new TemplateException('Invalid column definition for: ' . $name);
		}

		$db = DB::getInstance();
		$definition->name = $name;

		if (empty($definition->type)) {
			throw new TemplateException('Missing type for column: ' . $name);
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
			$definition->constraint = sprintf('CHECK (%s IS NULL OR datetime(%1$s) = %1$s)', $db->quoteIdentifier($name));
		}

		if (!in_array($definition->type, self::MODULE_TABLE_COLUMN_TYPES, true)) {
			throw new TemplateException(sprintf('Invalid column "%s": unknown type "%s"', $definition->type));
		}
		elseif (!is_bool($definition->null)) {
			throw new TemplateException(sprintf('Invalid column "%s": null can only be a boolean', $name));
		}
		elseif (isset($definition->default)
			&& strtoupper($definition->default) === 'CURRENT_TIMESTAMP'
			&& $definition->type !== 'DATETIME') {
			throw new TemplateException(sprintf('Invalid column "%s": default value "%s" is only valid for DATETIME type', $name, $definition->default));
		}
		elseif (isset($definition->fk_on_delete)
			&& !in_array($definition->fk_on_delete, ['SET NULL', 'RESTRICT', 'CASCADE'], true)) {
			throw new TemplateException(sprintf('Invalid column "%s": unknown foreign key constraint "%s"', $name, $definition->fk_on_delete));
		}
		elseif (isset($definition->fk_table)
			&& !isset($definition->fk_column)) {
			throw new TemplateException(sprintf('Invalid column "%s" foreign key: table is defined, but no column is defined', $name));
		}
		elseif (isset($definition->fk_table)
			&& !isset($definition->fk_column)) {
			throw new TemplateException(sprintf('Invalid column "%s" foreign key: table is defined, but no column is defined', $name));
		}
		elseif (isset($definition->fk_table)
			&& !preg_match('/^!?[a-z]+(?:_[a-z]+)*$/', $definition->fk_table)) {
			throw new TemplateException(sprintf('Invalid column "%s" foreign key: invalid table name "%s"', $name, $definition->fk_table));
		}
		elseif (isset($definition->fk_column)
			&& !preg_match(Module::TABLE_NAME_REGEXP, $definition->fk_column)) {
			throw new TemplateException(sprintf('Invalid column "%s" foreign key column name "%s"', $name, $definition->fk_column));
		}
		elseif (isset($definition->fk_on_delete)
			&& strtoupper($definition->fk_on_delete) === 'RESTRICT'
			&& substr($definition->fk_table, 0, 1) === '!') {
			throw new TemplateException(sprintf('Invalid column "%s": foreign key constraint "%s" is only valid for internal module tables', $name, $definition->fk_on_delete));
		}
		elseif (isset($definition->unique)
			&& $definition->unique !== true
			&& !preg_match(Module::TABLE_NAME_REGEXP, $definition->unique)) {
			throw new TemplateException(sprintf('Invalid column "%s": invalid unique index name "%s"', $name, $definition->unique));
		}


		if (isset($definition->fk_table)) {
			if (substr($definition->fk_table, 0, 1) === '!') {
				$definition->fk_table = substr($definition->fk_table, 1);
			}
			else {
				$definition->fk_table = Modules::getModuleTableName($module->name, $definition->fk_table);
			}

			// Validate foreign key table and column exists
			static $fk_tables = [];

			$fk_tables[$definition->fk_table] ??= $db->getTableSchema($definition->fk_table);

			if (!$fk_tables[$definition->fk_table]) {
				throw new TemplateException(sprintf('Invalid foreign key: "%s" table does not exist', $definition->fk_table));
			}
			elseif (!array_key_exists($definition->fk_column, $fk_tables[$definition->fk_table]['columns'])) {
				throw new TemplateException(sprintf('Invalid foreign key: "%s" column does not exist', $definition->fk_column));
			}
			// apparently sqlite doesn't care about column type matching foreign key column and local column, that's good
		}

		$sql = sprintf('%s %s %s', $db->quoteIdentifier($name), $definition->type, $definition->null ? 'NULL' : 'NOT NULL');

		if (isset($definition->default)) {
			$sql .= ' DEFAULT ';

			if (strtoupper($definition->default) === 'CURRENT_TIMESTAMP'
				|| ctype_digit($definition->default)) {
				$sql .= $definition->default;
			}
			else {
				$sql .= $db->quote($definition->default);
			}
		}

		if (isset($definition->fk_table)) {
			$sql .= sprintf(' REFERENCES %s (%s) ON DELETE %s',
				$db->quoteIdentifier($definition->fk_table),
				$db->quoteIdentifier($definition->fk_column),
				$definition->fk_on_delete ?? 'SET NULL'
			);
		}

		if ($definition->unique === true) {
			$sql .= ' UNIQUE';
		}

		$sql .= ',';

		if (isset($definition->comment)) {
			// Make sure the user cannot escape comment
			$comment = preg_replace('/[^a-zA-Z0-9_\p{L}]+/u', ' ', $definition->comment);
			$comment = mb_substr($comment, 0, 150);

			if (trim($comment) !== '') {
				$sql .= ' -- ' . $comment;
			}
		}

		$definition->sql = $sql;
		return $definition;
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
}
