<?php

namespace Paheko\UserTemplate\Modules;

use Paheko\UserTemplate\Functions;
use Paheko\UserTemplate\Sections;
use Paheko\UserTemplate\UserTemplate;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Template;
use Paheko\TemplateException;

/**
 * @deprecated
 * @todo remove when all modules have been migrated to tables
 */
class LegacySections
{
	static protected $modules_tables = [];

	/**
	 * @deprecated
	 */
	static public function load(array $params, UserTemplate $tpl, int $line): \Generator
	{
		$db = DB::getInstance();

		if (isset($params['module'])) {
			$name = $params['module'];
			$table = Modules::getModuleTableName($name, 'documents');
			self::$modules_tables[$table] ??= $db->test('sqlite_master', 'type = \'table\' AND name = ?', $table);
		}
		elseif (isset($tpl->module->name)) {
			$name = $tpl->module->name;
			$table = $tpl->module->getDocumentsTableName();
			self::$modules_tables[$table] ??= $tpl->module->hasDocumentsTable();
		}
		else {
			throw new TemplateException('Unique module name could not be found');
		}

		$has_table = self::$modules_tables[$table];

		if (!$has_table) {
			return;
		}

		unset($params['module']);
		$params['tables'] = sprintf('%s AS a', $table);

		// Cannot use json_each with authorizer before SQLite 3.41.0
		// @see https://sqlite.org/forum/forumpost/d28110be11
		if (isset($params['each']) && !$db->hasFeatures('json_each_readonly')) {
			$t = 'module_tmp_each' . md5($params['each']);

			// We create a temporary table, to get around authorizer issues in SQLite
			$db->exec(sprintf('DROP TABLE IF EXISTS %s; CREATE TEMP TABLE IF NOT EXISTS %1$s (id, key, value, document);', $t));
			$db->exec(sprintf('INSERT INTO %s SELECT a.id, a.key, value, a.document FROM %s AS a, json_each(a.document, %s);',
				$t, $table, $db->quote('$.' . trim($params['each']))
			));

			$params['tables'] = $t . ' AS a';
			$params['select'] = 'value';
			unset($params['each']);
		}
		elseif (isset($params['each'])) {
			$params['select'] = 'value';
			$params['tables'] = sprintf('%s AS a, json_each(a.document, %s)', $table, $db->quote('$.' . trim($params['each'])));
			unset($params['each']);
		}

		if (!isset($params['where'])) {
			$params['where'] = '1';
		}
		else {
			$params['where'] = '(' . self::_moduleReplaceJSONExtract($params['where'], $table) . ')';
		}

		if (array_key_exists('key', $params)) {
			$params['where'] .= ' AND key = :key';
			$params['limit'] = 1;
			$params[':key'] = $params['key'];
			unset($params['key']);
		}
		elseif (array_key_exists('id', $params)) {
			$params['where'] .= ' AND id = :id';
			$params['limit'] = 1;
			$params[':id'] = $params['id'];
			unset($params['id']);
		}

		// Replace '$.name = "value"' parameters with json_extract
		foreach ($params as $key => $value) {
			$k = substr($key, 0, 1);
			if ($k == ':' || in_array($key, Sections::SQL_RESERVED_PARAMS)) {
				continue;
			}

			if (is_bool($value)) {
				$v = '= ' . (int) $value;
			}
			elseif (null === $value) {
				$v = 'IS NULL';
			}
			else {
				$v = sprintf(':quick_%s', sha1($key));
				$params[$v] = $value;
				$v = '= ' . $v;
			}

			$params['where'] .= sprintf(' AND json_extract(document, %s) %s', $db->quote('$.' . $key), $v);
			unset($params[$key]);
		}

		$s = 'a.id, a.key, a.document AS json';

		if (isset($params['select'])) {
			$params['select'] = $s . ', ' . self::_moduleReplaceJSONExtract($params['select'], $table);
		}
		else {
			$params['select'] = $s;
		}

		if (isset($params['group'])) {
			$params['group'] = self::_moduleReplaceJSONExtract($params['group'], $table);
		}

		if (isset($params['having'])) {
			$params['having'] = self::_moduleReplaceJSONExtract($params['having'], $table);
		}

		if (isset($params['order'])) {
			$params['order'] = self::_moduleReplaceJSONExtract($params['order'], $table);
		}

		// Try to create an index if required
		self::_createModuleIndexes($table, $params['where']);

		$assign = $params['assign'] ?? null;
		unset($params['assign']);

		$query = Sections::sql($params, $tpl, $line);

		foreach ($query as $row) {
			if (isset($row['json'])) {
				$json = json_decode($row['json'], true);

				if (is_array($json)) {
					unset($row['json']);
					$row = array_merge($row, $json);
				}
			}

			if (isset($assign)) {
				$tpl::_assign(['var' => $assign, 'value' => $row], $tpl, $line);
			}

			yield $row;
		}
	}

	/**
	 * Creates indexes for json_extract expressions
	 */
	static protected function _createModuleIndexes(string $table, string $where): void
	{
		preg_match_all('/json_extract\s*\(\s*document\s*,\s*(?:\'(.*?)\'|\"(.*?)\")\s*\)/', $where, $match, PREG_SET_ORDER);

		if (!count($match)) {
			return;
		}

		$search_params = [];

		foreach ($match as $m) {
			$search_params[$m[2] ?? $m[1]] = $m[0];
		}

		if (!count($search_params)) {
			return;
		}

		ksort($search_params);
		$hash = sha1(implode('', array_keys($search_params)));

		static $indexes = [];

		// Don't try to create index more than once
		if (in_array($table . $hash, $indexes)) {
			return;
		}

		$db = DB::getInstance();
		$sql = sprintf('CREATE INDEX IF NOT EXISTS %s_auto_%s ON %1$s (%s);', $table, $hash, implode(', ', $search_params));

		try {
			$db->exec($sql);
			$indexes[] = $table . $hash;
		}
		catch (DB_Exception $e) {
			throw new TemplateException(sprintf("Impossible de créer l'index, erreur SQL :\n%s\n\nRequête exécutée :\n%s", $db->lastErrorMsg(), $sql));
		}
	}

	static protected function _getModuleColumnsFromSchema(string $schema, ?string $columns, UserTemplate $tpl, int $line): array
	{
		$schema = Functions::_readFile($schema, 'schema', $tpl, $line);
		$schema = json_decode($schema, true);

		if (!$schema) {
			throw new TemplateException(sprintf("ligne %d: impossible de lire le schéma:\n%s",
				$line, json_last_error_msg()));
		}

		if (empty($schema['properties'])) {
			return [];
		}

		$out = [];

		if (null !== $columns) {
			$columns = explode(',', $columns);
			$columns = array_map('trim', $columns);
		}
		else {
			$columns = array_keys($schema['properties']);
		}

		foreach ($columns as $key) {
			$rule = $schema['properties'][$key] ?? null;

			// This column is not in the schema
			if (!$rule || !isset($rule['type'])) {
				continue;
			}

			$types = is_array($rule['type']) ? $rule['type'] : [$rule['type']];

			$out[$key] = [
				'label' => $rule['description'] ?? null,
				'select' => sprintf('json_extract(document, \'$.%s\')', $key),
				'_json_decode' => in_array('array', $types) || in_array('object', $types),
			];
		}

		return $out;
	}

	static public function _moduleReplaceJSONExtract(string $str, string $table): string
	{
		$str = str_replace('@TABLE', $table, $str);

		if (!strstr($str, '$')) {
			return $str;
		}

		$db = DB::getInstance();

		return preg_replace_callback(
			'/(?:([\w\d]+)\.)?\$(\$[\[\.][\w\d\.\[\]#]+)/',
			fn ($m) => sprintf('json_extract(%sdocument, %s)',
				!empty($m[1]) ? $db->quoteIdentifier($m[1]) . '.' : '',
				$db->quote($m[2])
			),
			$str
		);
	}

	/**
	 * @deprecated
	 */
	static public function list(array $params, UserTemplate $tpl, int $line): \Generator
	{
		if (empty($params['schema']) && empty($params['select'])) {
			throw new TemplateException('Missing schema parameter');
		}

		$db = DB::getInstance();

		if (isset($params['module'])) {
			$name = $params['module'];
			$table = Modules::getModuleTableName($name, 'documents');
			self::$modules_tables[$table] ??= $db->test('sqlite_master', 'type = \'table\' AND name = ?', $table);
		}
		elseif (isset($tpl->module->name)) {
			$name = $tpl->module->name;
			$table = $tpl->module->getDocumentsTableName();
			self::$modules_tables[$table] ??= $tpl->module->hasDocumentsTable();
		}
		else {
			throw new TemplateException('Unique module name could not be found');
		}

		$has_table = self::$modules_tables[$table];

		if (!$has_table) {
			return;
		}

		if (!isset($params['where'])) {
			$where = '1';
		}
		else {
			$where = self::_moduleReplaceJSONExtract($params['where'], $table);
		}

		$columns = [];

		if (!empty($params['select'])) {
			foreach (explode(';', $params['select']) as $i => $c) {
				$c = trim($c);

				$pos = strripos($c, ' AS ');

				if ($pos) {
					$select = trim(substr($c, 0, $pos));
					$label = str_replace("''", "'", trim(substr($c, $pos + 5), ' \'"'));
				}
				else {
					$select = $c;
					$label = null;
				}

				if ($select === '*') {
					throw new TemplateException(sprintf('Line %d: "*" cannot be used in "select" parameter', $line));
				}

				$select = self::_moduleReplaceJSONExtract($select, $table);

				$columns['col' . ($i + 1)] = compact('label', 'select');
			}

			if (isset($params['order'])) {
				if (!is_int($params['order']) && !ctype_digit($params['order'])) {
					throw new TemplateException(sprintf('Line %d: "order" parameter must be the number of the column (starting from 1)', $line));
				}

				$params['order'] = 'col' . (int)$params['order'];
			}
		}
		else {
			$columns = self::_getModuleColumnsFromSchema($params['schema'], $params['columns'] ?? null, $tpl, $line);
		}

		$columns['id'] = [];
		$columns['key'] = [];
		$columns['document'] = [];

		$list = new DynamicList($columns, $table);

		static $reserved_keywords = ['max', 'order', 'desc', 'debug', 'explain', 'schema', 'columns', 'select', 'where', 'module', 'disable_user_ordering', 'disable_user_sort', 'check', 'export', 'group', 'count'];

		foreach ($params as $key => $value) {
			if ($key[0] == ':') {
				if (false !== strpos($where, $key)) {
					$list->setParameter(substr($key, 1), $value);
				}
			}
			elseif (!in_array($key, $reserved_keywords)) {
				$hash = sha1($key);
				$where .= sprintf(' AND json_extract(document, %s) = :quick_%s', $db->quote('$.' . $key), $hash);
				$list->setParameter('quick_' . $hash, $value);
			}
		}

		$list->setConditions($where);
		$size = (int) ($params['max'] ?? 50);
		$list->setPageSize($size === 0 ? null : $size);

		if (isset($params['order'])) {
			$list->orderBy($params['order'], $params['desc'] ?? false);
		}

		if (isset($params['group'])) {
			$list->groupBy(self::_moduleReplaceJSONExtract($params['group'], $table));
		}

		$list->setModifier(function(&$row) use ($columns) {
			$row->original = clone $row;
			unset($row->original->id, $row->original->key, $row->original->document);

			// Decode arrays/objects
			foreach ($columns as $name => $column) {
				if (!empty($column['_json_decode']) && isset($row->$name) && is_string($row->$name)) {
					$row->$name = json_decode($row->$name, true);
				}
			}

			if (null !== $row->document) {
				$row = array_merge(json_decode($row->document, true), (array)$row);
			}
			else {
				$row = (array) $row;
			}
		});


		$list->setExportCallback(function(&$row) {
			$row = $row['original'];
			foreach ($row as $key => $value) {
				if (!is_string($value) || substr($value, 0, 1) !== '{' || substr($value, -1) !== '}') {
					continue;
				}

				$row->$key = Utils::export_value(json_decode($value));
			}
		});

		// Try to create an index if required
		self::_createModuleIndexes($table, $where);

		if (empty($params['disable_user_ordering'])
			&& empty($params['disable_user_sort'])) {
			$list->loadFromQueryString();
		}

		if (!empty($params['debug'])) {
			Sections::_debug($list->SQL());
		}

		try {
			$i = $list->iterate();

			// If there is nothing to iterate, just stop
			if (!$i->valid()) {
				return;
			}
		}
		catch (DB_Exception $e) {
			throw new TemplateException(sprintf("Line %d: invalid SQL query: %s\nQuery: %s", $line, $e->getMessage(), $list->SQL()));
		}

		$tpl = new Template('common/dynamic_list_head.tpl', Template::getInstance());

		if (!empty($params['export'])) {
			$export_params = ['right' => true];
			//$export_params['table'] = $params['export'] === 'table'; // Table export is currently not working in modules FIXME

			printf('<p class="actions">%s</p>', CommonFunctions::exportmenu($export_params));
		}

		$tpl->assign(compact('list'));
		$tpl->assign('check', $params['check'] ?? false);
		$tpl->assign('disable_user_sort', boolval($params['disable_user_sort'] ?? ($params['disable_user_ordering'] ?? false)));
		$tpl->display();

		yield from $i;

		echo '</tbody>';
		echo '</table>';

		echo $list->getHTMLPagination();
	}
}
