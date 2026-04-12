<?php

namespace Paheko\UserTemplate\Modules;

use Paheko\TemplateException;
use Paheko\UserTemplate\Functions;
use Paheko\UserTemplate\LegacySections;
use Paheko\UserTemplate\UserTemplate;
use Paheko\DB;
use Paheko\Utils;
use KD2\JSONSchema;

/**
 * @deprecated
 * @todo remove when all modules have been migrated to tables
 */
class LegacyFunctions
{
	/**
	 * @deprecated
	 */
	static public function save(array $params, UserTemplate $tpl, int $line): void
	{
		if (!$tpl->module) {
			throw new TemplateException('Module name could not be found');
		}

		$db = DB::getInstance();

		if (isset($params['from'])) {
			if (!is_array($params['from'])) {
				throw new TemplateException('"from" parameter is not an array');
			}

			$from = $params['from'];
			unset($params['from']);
			$db->begin();

			foreach ($from as $key => $row) {
				if (!is_array($row) && !is_object($row)) {
					throw new TemplateException('"from" parameter item is not an array on index: ' . $key);
				}

				self::save(array_merge((array)$row, $params), $tpl, $line);
			}

			$db->commit();
			return;
		}

		$table = $tpl->module->getDocumentsTableName();

		if (isset($params['key'])) {
			if ($params['key'] === 'uuid') {
				$params['key'] = Utils::uuid();
			}

			$field = 'key';
			$where_value = $params['key'];
		}
		elseif (isset($params['id'])) {
			$field = 'id';
			$where_value = $params['id'];
		}
		else {
			$where_value = null;
			$field = null;
		}

		$key = $params['key'] ?? null;
		$assign_new_id = $params['assign_new_id'] ?? null;
		$validate = $params['validate_schema'] ?? null;
		$validate_only = $params['validate_only'] ?? null;
		$replace = !empty($params['replace']);
		$result = null;

		unset($params['key'], $params['id'], $params['assign_new_id'], $params['validate_schema'],
			$params['validate_only'], $params['replace']);

		if ($key === 'config' && !$replace) {
			$result = $db->firstColumn(sprintf('SELECT config FROM %s WHERE name = ?;', Module::TABLE), $tpl->module->name);
		}
		elseif ($key !== 'config') {
			static $modules_tables = [];

			// Don't try to create table for each save statement
			if (!in_array($table, $modules_tables)) {
				$db->exec(sprintf('
					CREATE TABLE IF NOT EXISTS %s (
						id INTEGER NOT NULL PRIMARY KEY,
						key TEXT NULL,
						document TEXT NOT NULL
					);
					CREATE UNIQUE INDEX IF NOT EXISTS %1$s_key ON %1$s (key);', $table));
				$modules_tables[] = $table;
			}

			if ($field && !$replace) {
				$result = $db->firstColumn(sprintf('SELECT document FROM %s WHERE %s;', $table, ($field . ' = ?')), $where_value);
			}
		}

		// Merge before update
		if ($result) {
			$result = json_decode((string) $result, true);
			$params = array_merge($result, $params);
		}

		if (!empty($validate)) {
			static $schemas = [];

			if (!isset($schemas[$validate])) {
				$schema = Functions::_readFile($validate, 'validate_schema', $tpl, $line);

				if ($validate_only && is_string($validate_only)) {
					$validate_only = explode(',', $validate_only);
					$validate_only = array_map('trim', $validate_only);
				}
				else {
					$validate_only = null;
				}

				try {
					$schemas[$validate] = JSONSchema::fromString($schema);
				}
				catch (\LogicException $e) {
					throw new TemplateException($e->getMessage(), 0, $e);
				}
			}

			$s = $schemas[$validate];

			try {
				if ($validate_only) {
					$s->validateOnly($params, $validate_only);
				}
				else {
					$s->validate($params);
				}
			}
			catch (\RuntimeException $e) {
				throw new TemplateException(sprintf("impossible de valider le schéma:\n%s\n\n%s",
					$e->getMessage(), json_encode($params, JSON_PRETTY_PRINT)));
			}
		}

		$value = json_encode($params);

		if ($key === 'config') {
			$db->update(Module::TABLE, ['config' => $value], 'name = :name', ['name' => $tpl->module->name]);
			return;
		}

		$document = $value;

		if (!$result) {
			$db->begin();

			if ($field && $where_value) {
				$db->delete($table, $field . ' = ?', $where_value);
			}

			$id = null;
			$key = Utils::uuid();
			$db->insert($table, compact('id', 'document', 'key'));
			$db->commit();

			if ($assign_new_id) {
				$tpl->assign($assign_new_id, $db->lastInsertId());
			}
		}
		else {
			$db->update($table, compact('document'), sprintf('%s = :match', $field), ['match' => $where_value]);
		}
	}

	/**
	 * @deprecated
	 */
	static public function delete(array $params, UserTemplate $tpl, int $line): void
	{
		if (!$tpl->module) {
			throw new TemplateException('Module name could not be found');
		}

		$db = DB::getInstance();
		$table = $tpl->module->getDocumentsTableName();

		// No table? No problem!
		if (!$tpl->module->hasDocumentsTable()) {
			return;
		}

		$where = [];
		$args = [];
		$i = 0;

		foreach ($params as $key => $value) {
			if ($key[0] == ':') {
				$args[substr($key, 1)] = $value;
			}
			elseif ($key == 'where') {
				$where[] = LegacySections::_moduleReplaceJSONExtract($value, $table);
			}
			else {
				if ($key == 'id') {
					$value = (int) $value;
				}

				if ($key !== 'id' && $key !== 'key') {
					$args['key_' . $i] = '$.' . $key;
					$key = sprintf('json_extract(document, :key_%d)', $i);
				}

				$where[] = $key . ' = :value_' . $i;
				$args['value_' . $i] = $value;
				$i++;
			}
		}

		if (!count($where)) {
			throw new TemplateException('Missing parameters for delete');
		}

		$where = implode(' AND ', $where);
		$db->delete($table, $where, $args);
	}
}
