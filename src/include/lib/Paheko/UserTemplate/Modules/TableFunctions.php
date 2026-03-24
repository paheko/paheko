<?php

namespace Paheko\UserTemplate\Modules;

use Paheko\TemplateException;
use Paheko\UserTemplate\UserTemplate;
use Paheko\UserTemplate\Modules;
use Paheko\DB;
use Paheko\Utils;

use Paheko\Entities\Module;

use KD2\DB\DB_Exception;

use stdClass;

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
			(?:\s+REFERENCES\s+((?-i)!?[a-z0-9_]+)\s*\(((?-i)[a-z0-9_]+)\)(?:\s+ON\s+DELETE\s+(SET\s+NULL|RESTRICT|CASCADE))?)?
			(\s+UNIQUE(?:\s+\((?-i)[a-z0-9_]+\))?)?
			(?:\s+COMMENT\s+("[^"]*"|\'[^\']*\'))?$/xi';

	static protected function _getModuleTableSQLDefinition(string $table, ?string $comment, array $columns): string
	{
		if (null !== $comment) {
			// Make sure comment doesn't have line breaks, and is not too long
			$comment = mb_substr(preg_replace('/\s+/', ' ', $comment), 0, 150);
			$comment = '-- ' . $comment . "\n";
		}

		$columns_str = [];

		foreach ($columns as $name => $definition) {
			if ($name === 'id' || $name === 'key') {
				throw new TemplateException(sprintf('The column name "%s" is already used (built-in default of table)', $name));
			}

			$columns_str[] = $definition->sql;
		}

		$columns_str = array_merge([
			'id INTEGER NOT NULL,',
			'key TEXT NOT NULL UNIQUE,',
		], $columns_str);


		// putting the primary key definition here instead of in the column definition
		// is better as it avoids having to delete the comma from the last column definition
		$columns_str[] = 'PRIMARY KEY (id)';

		$db = DB::getInstance();
		$sql = sprintf("CREATE TABLE IF NOT EXISTS %s\n%s(\n  %s\n);", $db->quoteIdentifier($table), $comment, implode("\n  ", $columns_str));

		return $sql;
	}

	/**
	 * Create/rename/delete module table
	 */
	static public function table(array $params, UserTemplate $tpl, int $line): void
	{
		$module = $tpl->module ?? null;

		if (!$module) {
			throw new TemplateException('Module name could not be found');
		}

		if (!$module->version) {
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
		$table_name = Modules::getModuleTableName($module->name, $name);

		if ($action === 'create') {
			if ($db->hasTable($table_name)) {
				throw new TemplateException('This table already exists: ' . $table_name);
			}

			foreach ($params as $name => $definition) {
				if ($name === 'comment') {
					continue;
				}

				$columns[$name] = self::_getModuleColumnDefinition($module, $name, $definition);
			}

			$table->set('columns', $columns);


			$sql = self::_getModuleTableSQLDefinition($table_name, $params['comment'] ?? null, $columns);
		}
		elseif ($action === 'rename') {
			$new_name = $params['to'] ?? '';

			if (!preg_match(Module::TABLE_NAME_REGEXP, $new_name)) {
				throw new TemplateException('Invalid new table name: ' . $new_name);
			}

			$table->set('name', $new_name);
			$new_name = Modules::getTableName($module->name, $new_name);

			if (!$db->hasTable($table_name)) {
				throw new TemplateException('This table does not exist: ' . $table_name);
			}

			if ($db->hasTable($new_name)) {
				throw new TemplateException('Cannot rename, as target table name exists: ' . $new_name);
			}

			$sql = sprintf('ALTER TABLE %s RENAME TO %s;', $db->quoteIdentifier($table_name), $db->quoteIdentifier($new_name));
		}
		elseif ($action === 'delete') {
			if (!$db->hasTable($table_name)) {
				throw new TemplateException('This table does not exist: ' . $table_name);
			}

			$sql = sprintf('DROP TABLE IF EXISTS %s;', $db->quoteIdentifier($table_name));
		}

		// set authorizer to only allow working on this specific table
		$db->enableTableAuthorizer($table_name);

		$db->begin();

		// There shouldn't be any error due to the user here, so we don't try/catch
		$db->exec($sql);

		$table->save();
		$db->commit();

		// fall back to safety authorizer
		$db->enableSafetyAuthorizer();
	}

	/**
	 * Create/rename/delete/modify table column
	 */
	static public function column(array $params, UserTemplate $tpl, int $line): void
	{
		$module = $tpl->module ?? null;

		if (!$module) {
			throw new TemplateException('Module name could not be found');
		}

		if (!$module->version) {
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

		$table_name = $params['table'] ?? '';
		$table_name = Modules::getModuleTableName($module->name, $table_name);

		$db = DB::getInstance();

		if (!$db->hasTable($table_name)) {
			throw new TemplateException('This table doesn\'t exist: ' . $table_name);
		}

		$table = $module->getTable($params['table']);

		if (!$table) {
			throw new \LogicException('Missing metadata for table: ' . $table_name);

		}

		$columns = $table->columns;

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
		$module = $tpl->module ?? null;

		if (!$module) {
			throw new TemplateException('Module name could not be found');
		}

		$key = $params['key'] ?? null;
		$id = $params['id'] ?? null;

		if (!array_key_exists('table', $params)
			&& $key !== 'config') {
			LegacyFunctions::save($params, $tpl, $line);
			return;
		}

		unset($params['key'], $params['id']);
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

		$table = Modules::getModuleTableName($module->name, $params['table']);
		$sql_params = [];
		$where = null;

		if ($id) {
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
