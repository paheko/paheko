<?php

namespace Paheko\UserTemplate\Modules;

use Paheko\TemplateException;
use Paheko\ValidationException;
use Paheko\UserTemplate\UserTemplate;
use Paheko\UserTemplate\Modules;
use Paheko\UserTemplate\Sections;
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
		'insert',
	];

	const COMPILE_FUNCTIONS_LIST = [
		':insert' => 'compile_insert',
	];

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

		if (($params['delete'] ?? null) === '@DOCUMENTS') {
			$db->exec(sprintf('DROP TABLE IF EXISTS %s;', $db->quoteIdentifier($tpl->module->documents_table_name())));
			return;
		}

		$actions = ['create', 'delete', 'rename', 'export'];

		$action = array_intersect_key($params, array_flip($actions));

		if (count($action) > 1) {
			throw new TemplateException('Cannot specify more than one table action');
		}
		elseif (!count($action)) {
			throw new TemplateException('No table action was specified');
		}

		$action = key($action);
		$name = $params[$action];
		unset($params[$action]);

		if (!is_string($name)) {
			throw new TemplateException('Invalid column name: not a string');
		}

		// Make sure we cannot modify tables outside of migration.tpl
		if ($action !== 'export'
			&& Utils::basename($tpl->_tpl_path) !== Module::MIGRATION_FILE) {
			throw new TemplateException('This table action cannot be performed outside of ' . Module::MIGRATION_FILE);
		}

		$db = DB::getInstance();
		$table = $module->getTable($name);

		if ($action === 'create') {
			if ($table) {
				throw new TemplateException('This table already exists: ' . $name);
			}

			$comment = $params['comment'] ?? null;
			unset($params['comment']);

			try {
				$table = $module->createTable($name, $comment, $params);
			}
			catch (ValidationException $e) {
				throw new TemplateException($e->getMessage(), $e->getCode(), $e);
			}
		}
		elseif (!$table) {
			throw new TemplateException('This table does not exist: ' . $name);
		}

		if ($action === 'export') {
			if (empty($params['assign'])) {
				throw new TemplateException('Missing "assign" parameter for export');
			}

			$export = $table->asArray();
			unset($export['id'], $export['id_module']);
			$export['sql'] = $table->getSQL();
			$tpl->assign($params['assign'], $export);
			return;
		}
		elseif ($action === 'rename') {
			$table->set('name', $params['to']);
		}

		try {
			if ($action === 'delete') {
				$table->delete();
			}
			else {
				$table->save();
			}
		}
		catch (\InvalidArgumentException|ValidationException $e) {
			throw new TemplateException($e->getMessage(), $e->getCode(), $e);
		}
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

		// Make sure we cannot modify tables outside of migration.tpl
		if (Utils::basename($tpl->_tpl_path) !== Module::MIGRATION_FILE) {
			throw new TemplateException('This table action cannot be performed outside of ' . Module::MIGRATION_FILE);
		}

		$table_name = $params['table'] ?? '';
		$table = $module->getTable($table_name);

		if (!$table) {
			throw new TemplateException('This table doesn\'t exist: ' . $table_name);
		}

		$actions = ['create', 'delete', 'rename', 'modify'];

		$action = array_intersect_key($params, array_flip($actions));

		if (count($action) > 1) {
			throw new TemplateException('Cannot specify more than one column action');
		}
		elseif (!count($action)) {
			throw new TemplateException('No column action was specified');
		}

		$action = key($action);
		$name = $params[$action];

		if (!is_string($name)) {
			throw new TemplateException('Invalid column name: not a string');
		}

		// Verify parameters
		if ($action === 'create' || $action === 'modify') {
			if (!isset($params['definition'])) {
				throw new TemplateException('No column definition was passed');
			}
			elseif (!is_string($params['definition']) && !is_array($params['definition'])) {
				throw new TemplateException('Invalid column definition type: must be a string of an array');
			}
		}
		elseif ($action === 'rename') {
			if (!isset($params['to'])) {
				throw new TemplateException('No target column name was passed');
			}
			elseif (!is_string($params['to'])) {
				throw new TemplateException('Target column name is not a string');
			}
		}

		try {
			if ($action === 'create') {
				$table->addColumn($name, $params['definition']);
			}
			elseif ($action === 'rename') {
				$table->renameColumn($name, $params['to']);
			}
			elseif ($action === 'delete') {
				$table->dropColumn($name);
			}
			elseif ($action === 'modify') {
				$table->modifyColumn($name, $params['definition']);
			}
		}
		catch (ValidationException $e) {
			throw new TemplateException($e->getMessage());
		}

		// We are saving / recreating the table after each change
		// This is not optimal, but because this is in a transaction, it should be OK
		$table->save();
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

		if (array_key_exists('assign_new_id', $params)) {
			throw new TemplateException('Parameter "assign_new_id" has been removed, use "assign" instead');
		}

		if (array_key_exists('replace', $params)) {
			throw new TemplateException('Parameter "replace" has been removed');
		}

		if (array_key_exists('from', $params)) {
			throw new TemplateException('Parameter "from" has been removed');
		}

		if (array_key_exists('validate_schema', $params)) {
			throw new TemplateException('Parameter "validate_schema" has been removed');
		}

		if (array_key_exists('validate_only', $params)) {
			throw new TemplateException('Parameter "validate_only" has been removed');
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

	static public function compile_insert(string $name, string $params, UserTemplate $tpl, int $line): string
	{
		$params = Sections::_replaceVariablesInSQL($params, 'INSERT ');
		return $tpl->_function('insert', $params, $line);
	}

	static public function insert(array $params, UserTemplate $tpl, int $line): void
	{
		var_dump($params); exit;
		$db = DB::getInstance();
		$sql = $params['sql'];
		$sql = str_replace('@MODULE_', $tpl->module->table_prefix(), $sql);

		// set authorizer to only allow working on modules tables
		$db->enableTableAuthorizer($tpl->module->getTablesNames());

		try {
			$db->exec($sql);
		}
		catch (DB_Exception $e) {
			throw new TemplateException($e->getMessage(), 0, $e);
		}

		$db->enableSafetyAuthorizer();
	}
}
