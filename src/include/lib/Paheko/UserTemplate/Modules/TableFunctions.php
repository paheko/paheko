<?php

namespace Paheko\UserTemplate\Modules;

use Paheko\TemplateException;
use Paheko\UserTemplate\UserTemplate;
use Paheko\UserTemplate\Modules;
use Paheko\DB;
use Paheko\Utils;

use Paheko\Entities\Module;

use KD2\DB\DB_Exception;

class TableFunctions
{
	const FUNCTIONS_LIST = [
		'table',
		'column',
		'save',
		'delete',
	];

	const MODULE_TABLE_COLUMN_TYPES = [
		'TEXT',
		'INTEGER',
		'DATETIME',
		'REAL',
		'NUMERIC',
	];

	const MODULE_COLUMN_DEFINITION_REGEXP = '/^(TEXT|INT|INTEGER|DATETIME|REAL|FLOAT|NUMERIC)
			(?:\s+(NOT\s+NULL|NULL))?
			(?:\s+DEFAULT\s+("[^"]*"|\'[^\']*\'|\d+|CURRENT_TIMESTAMP))?
			(?:\s+REFERENCES\s+((?-i)[a-z0-9_]+)\s*\((?-i)[a-z0-9_]+\)(?:\s+ON\s+DELETE\s+(SET\s+NULL|RESTRICT|CASCADE))?)?
			(\s+UNIQUE(?:\s+\((?-i)[a-z0-9_]+\))?)?
			(?:\s+COMMENT\s+("[^"]*"|\'[^\']*\'))?$/xi';

	static protected function _getModuleTableSQLDefinition(string $name, ?string $comment, array $columns): string
	{
		if (null !== $comment) {
			// Make sure comment doesn't have line breaks, and is not too long
			$comment = mb_substr(preg_replace('/\s+/', ' ', $comment), 0, 150);
			$comment = '-- ' . $comment . "\n";
		}

		$columns = [
			'id INTEGER NOT NULL,',
			'key TEXT NOT NULL UNIQUE,',
		];

		foreach ($columns as $name => $definition) {
			if ($name === 'id' || $name === 'key') {
				throw new TemplateException(sprintf('The column name "%s" is already used (built-in default of table)', $name));
			}

			$columns[] = $definition->sql;
		}

		// putting the primary key definition here instead of in the column definition
		// is better as it avoids having to delete the comma from the last column definition
		$columns[] = 'PRIMARY KEY (id)';

		$db = DB::getInstance();
		$sql = sprintf("CREATE TABLE IF NOT EXISTS %s\n%s(\n  %s);", $db->quoteIdentifier($table), $comment, implode("\n  ", $columns));
		return $sql;
	}

	/**
	 * Verify column definition and export it to SQL code
	 */
	static protected function _getModuleColumnDefinition(Module $module, string $name, ?string $definition_str, ?array $definition, array &$unique_indexes): stdClass
	{
		if (null !== $definition_str) {
			if (!preg_match(self::MODULE_COLUMN_DEFINITION_REGEXP, $definition_str, $match)) {
				throw new TemplateException(sprintf('Invalid column "%s" definition: %s', $name, $definition_str));
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
		else {
			static $keys = null;

			if (null === $keys) {
				$keys = ['type', 'null', 'default', 'fk_table', 'fk_column', 'fk_on_delete', 'unique', 'comment'];
				$keys = array_flip($keys);
			}

			$definition = (object) array_intersect_key($definition, $keys);
		}

		$db = DB::getInstance();

		$definition->type = strtoupper($type);
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
		elseif (strtoupper($definition->default) === 'CURRENT_TIMESTAMP'
			&& !$definition->type !== 'DATETIME') {
			throw new TemplateException(sprintf('Invalid column "%s": default value "%s" is only valid for DATETIME type', $name, $definition->default));
		}
		elseif (!in_array($definition->fk_on_delete, ['SET NULL', 'RESTRICT', 'CASCADE'], true)) {
			throw new TemplateException(sprintf('Invalid column "%s": unknown foreign key constraint "%s"', $definition->fk_on_delete));
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
			&& !preg_match('/^!?[a-z]+(?:_[a-z]+)$/', $definition->fk_table)) {
			throw new TemplateException(sprintf('Invalid column "%s" foreign key table name "%s"', $name, $definition->fk_table));
		}
		elseif (isset($definition->fk_column)
			&& !preg_match(Module::TABLE_NAME_REGEXP, $definition->fk_column)) {
			throw new TemplateException(sprintf('Invalid column "%s" foreign key column name "%s"', $name, $definition->fk_column));
		}
		elseif (strtoupper($definition->fk_on_delete) === 'RESTRICT'
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

		$sql = $definition->type . ' ' . $definition->null ? 'NULL' : 'NOT NULL';

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

		if (isset($definition->unique)) {
			if ($definition->unique === true) {
				$sql .= ' UNIQUE';
			}
			else {
				$unique_indexes[$definition->unique] ??= [];
				$unique_indexes[$definition->unique][] = $name;
			}
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

	/**
	 * Create/rename/delete module table
	 */
	static public function table(array $params, UserTemplate $tpl, int $line): void
	{
		if (!$tpl->module) {
			throw new TemplateException('Module name could not be found');
		}

		if (!$tpl->module->version) {
			throw new TemplateException('A module cannot use the "table" function if it doesn\'t have a "version" in module.ini');
		}

		if (!empty($params['create'])) {
			$action = 'create';
		}
		elseif (!empty($params['delete'])) {
			$action = 'delete';
		}
		elseif (!empty($params['rename'])) {
			$action = 'rename';
		}
		else {
			throw new TemplateException('No action parameter was supplied');
		}

		$name = $params[$action];
		unset($params[$action]);

		$db = DB::getInstance();
		$table = Modules::getModuleTableName($tpl->module->name, $name);

		if ($action === 'create') {
			if ($db->hasTable($table)) {
				throw new TemplateException('This table already exists: ' . $table);
			}

			foreach ($params as $name => $definition) {
				if ($name === 'comment') {
					continue;
				}

				$columns[] = self::_getModuleColumnDefinition($tpl->module, $name, $code);
			}

			$sql = self::_getModuleTableSQLDefinition($table, $params['comment'] ?? null, $columns);
		}
		elseif ($action === 'rename') {
			$new_name = $params['to'] ?? '';

			if (!preg_match(Module::TABLE_NAME_REGEXP, $new_name)) {
				throw new TemplateException('Invalid new table name: ' . $new_name);
			}

			$new_name = 'module_' . $tpl->module->name . '_' . $new_name;

			if (!$db->hasTable($table)) {
				throw new TemplateException('This table does not exist: ' . $table);
			}

			if ($db->hasTable($new_name)) {
				throw new TemplateException('Cannot rename, as target table name exists: ' . $new_name);
			}

			$sql = sprintf('ALTER TABLE %s RENAME TO %s;', $db->quoteIdentifier($table), $db->quoteIdentifier($new_name));
		}
		elseif ($action === 'delete') {
			if (!$db->hasTable($table)) {
				throw new TemplateException('This table does not exist: ' . $table);
			}

			$sql = sprintf('DROP TABLE IF EXISTS %s;', $db->quoteIdentifier($table));
		}

		// set authorizer to only allow working on this specific table
		$db->enableTableAuthorizer($table);

		// There shouldn't be any error due to the user here, so we don't try/catch
		$db->exec($sql);

		// fall back to safety authorizer
		$db->enableSafetyAuthorizer();
	}

	/**
	 * Create/rename/delete/modify table column
	 */
	static public function column(array $params, UserTemplate $tpl, int $line): void
	{
		if (!$tpl->module) {
			throw new TemplateException('Module name could not be found');
		}

		if (!$tpl->module->version) {
			throw new TemplateException('A module cannot use the "column" function if it doesn\'t have a "version" in module.ini');
		}

		if (!empty($params['create'])) {
			$action = 'create';
		}
		elseif (!empty($params['delete'])) {
			$action = 'delete';
		}
		elseif (!empty($params['rename'])) {
			$action = 'rename';
		}
		elseif (!empty($params['modify'])) {
			$action = 'modify';
		}
		else {
			throw new TemplateException('No action parameter was supplied');
		}

		$column = $params[$action];
		unset($params[$action]);

		if (!preg_match(Module::TABLE_NAME_REGEXP, $column)) {
			throw new TemplateException('Invalid column name: ' . $column);
		}

		$table = $params['table'] ?? '';
		$table = Modules::getModuleTableName($tpl->module->name, $name);

		$db = DB::getInstance();

		if (!$db->hasTable($table)) {
			throw new TemplateException('This table doesn\'t exist: ' . $table);
		}

			$columns = self::_getModuleTableColumns($params['table']);

		if ($action === 'rename') {
			$new_name = $params['to'] ?? '';

			if (!preg_match(Module::TABLE_NAME_REGEXP, $new_name)) {
				throw new TemplateException('Invalid new column name: ' . $new_name);
			}

			if (array_key_exists($new_name, $columns)) {
				throw new TemplateException(sprintf('Cannot rename "%s" column to "%s": target column already exists', $column, $new_name));
			}

			$sql = sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s;', $db->quoteIdentifier($table), $db->quoteIdentifier($column), $db->quoteIdentifier($new_name));
		}
		else {
			if ($action === 'delete') {
				if (!array_key_exists($column, $columns)) {
					throw new TemplateException('Cannot delete this column as it doesn\'t exist: ' . $column);
				}

				unset($columns[$column]);
			}
			elseif ($action === 'modify') {
				if (!array_key_exists($column, $columns)) {
					throw new TemplateException('Cannot modify this column as it doesn\'t exist: ' . $column);
				}

				$definition = self::_getModuleColumnDefinition($column, $params['definition'] ?? null, $params);
				$columns[$column] = $definition;
			}
			elseif ($action === 'create') {
				if (array_key_exists($column, $columns)) {
					throw new TemplateException(sprintf('Cannot create "%s": target column name already exists', $column));
				}

				$definition = self::_getModuleColumnDefinition($column, $params['definition'] ?? null, $params);
				$columns[$column] = $definition;
			}
			else {
				throw new \LogicException('Invalid action: ' . $action);
			}

			$sql = sprintf('ALTER TABLE %s RENAME TO %s;', $db->quoteIdentifier($table), $db->quoteIdentifier($table . '_old'));
			$sql .= "\n";
			$sql .= $this->_getModuleTableSQLDefinition($params['table'], $columns);
			$sql = sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s;', $db->quoteIdentifier($table), $db->quoteIdentifier($column), $db->quoteIdentifier($new_name));
		}

		// set authorizer to only allow working on this specific table
		$db->enableTableAuthorizer($table);

		// There shouldn't be any error due to the user here, so we don't try/catch
		$db->exec($sql);

		// fall back to safety authorizer
		$db->enableSafetyAuthorizer();
	}

	/**
	 * Create or re-create a module table
	 * If the table exists already, it will be renamed, to recopy old data
	 */
	static protected function _createModuleTable(Module $module, string $name, array $columns): void
	{
		$table = Modules::getModuleTableName($module->name, $name);
		$overwrite = false;

		$db->begin();

		if ($db->hasTable($table)) {
			$overwrite = true;
			$db->exec(sprintf('ALTER TABLE %s RENAME TO %s;', $db->quoteIdentifier($table), $db->quoteIdentifier($table . '_old')));
		}

		// Actually create table
		$db->exec(self::_getModuleTableSQLDefinition($table, $columns));

		if ($overwrite) {
			$columns_names = array_keys($columns);
			$columns_names = array_map([$db, 'quoteIdenfier'], $columns_names);

			$sql = sprintf('INSERT INTO %s (%s) SELECT %s FROM %2$s;',
				$db->quoteIdentifier($table),
				$columns_names,
				$db->quoteIdentifier($table . '_old')
			);

			$sql .= sprintf("\nDROP TABLE %s;", $db->quoteIdentifier($table . '_old'));
			$db->exec($sql);
		}

		$db->commit();
	}

	/**
	 * UPDATE or INSERT into module table
	 */
	static public function save(array $params, UserTemplate $tpl, int $line): void
	{
		if (!$tpl->module) {
			throw new TemplateException('Module name could not be found');
		}

		$key = $params['key'] ?? null;

		if (!array_key_exists('table', $params)
			&& $key !== 'config') {
			LegacyFunctions::save($params, $tpl, $line);
			return;
		}

		unset($params['key']);
		$db = DB::getInstance();

		// Save module config
		if ($key === 'config') {
			$config = array_merge((array) $module->config, $params);

			// Don't save NULL values, NULL means removed
			$config = array_filter($config, fn($a) => !is_null($a));

			$module->set('config', (object) $config);
			$module->save();
			return;
		}

		$table = Modules::getModuleTableName($tpl->module->name, $params['table']);
		$sql_params = [];
		$where = null;

		if (!empty($params['id'])) {
			$where = 'id = :id';
			$sql_params['id'] = $id;
		}
		elseif ($key) {
			$where = 'key = :key';
			$sql_params['key'] = $key;
		}
		elseif (!empty($params['where'])) {
			$where = $params['where'];
		}

		$assign = $params['assign'] ?? null;
		unset($params['id'], $params['assign'], $params['table'], $params['where']);

		// Make sure arrays and objects are saved as strings
		foreach ($params as &$value) {
			if (!is_scalar($value) && !is_null($value)) {
				$value = json_encode($value);
			}
		}

		unset($value);

		// set authorizer to only allow working on this specific table
		$db->enableTableAuthorizer($table);

		try {
			if ($where) {
				$db->update($table, $params, $where, $sql_params);
			}
			else {
				$params['key'] = Utils::uuid();
				$db->insert($table, $params);
				$id = $db->lastInsertId();
			}
		}
		catch (DB_Exception $e) {
			throw new TemplateException($e->getMessage(), 0, $e);
		}

		// Re-enable default authorizer
		$db->enableSafetyAuthorizer();

		// Assign new row values
		if ($assign) {
			$tpl->assign($assign, $db->first(sprintf('SELECT * FROM %s WHERE id = ?;', $db->quoteIdentifier($table)), $id));
		}
	}

	/**
	 * DELETE from module table
	 */
	static public function delete(array $params, UserTemplate $tpl, int $line): void
	{
		if (!$tpl->module) {
			throw new TemplateException('Module name could not be found');
		}

		$db = DB::getInstance();

		// TODO: remove when support for JSON documents is removed
		if (!empty($params['legacy_data_table'])) {
			$db->exec(sprintf('DROP TABLE IF EXISTS %s;', $db->quoteIdentifier($tpl->module->data_table_name())));
			return;
		}

		if (!array_key_exists('table', $params)) {
			LegacyFunctions::delete($params, $tpl, $line);
			return;
		}

		$table = Modules::getModuleTableName($tpl->module->name, $params['table']);

		$sql_params = [];
		$where = null;

		if (!empty($params['id'])) {
			$where = 'id = :id';
			$sql_params['id'] = $id;
		}
		elseif ($key) {
			$where = 'key = :key';
			$sql_params['key'] = $key;
		}
		elseif (!empty($params['where'])) {
			$where = $params['where'];
		}

		if (!$where) {
			throw new TemplateException('Missing where clause for delete function');
		}

		// set authorizer to only allow working on this specific table
		$db->enableTableAuthorizer($table);

		try {
			// Delete rows
			$db->delete($table, $where, $sql_params);
		}
		catch (DB_Exception $e) {
			throw new TemplateException($e->getMessage(), 0, $e);
		}

		$db->enableSafetyAuthorizer();
	}
}
