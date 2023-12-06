<?php
namespace Paheko;

use KD2\ErrorManager;

require_once __DIR__ . '/../_inc.php';

$list = null;
$query = f('query') ?? qg('query');

$db = DB::getInstance();
$tables_list = $db->getGrouped('SELECT name, sql, NULL AS count, NULL AS schema FROM sqlite_master
	WHERE type = \'table\' AND name NOT LIKE \'files_search_%\' AND name NOT IN (\'sqlite_stat1\')
	ORDER BY name;');

if (qg('table') && array_key_exists(qg('table'), $tables_list)) {
	$table = qg('table');
	$all_columns = $db->get(sprintf('PRAGMA table_info(%s);', $db->quoteIdentifier($table)));

	if (!$all_columns) {
		throw new UserException('This table does not exist');
	}

	$is_module = 0 === strpos($table, 'module_data_');

	$columns = [];

	foreach ($all_columns as $c) {
		$columns[$c->name] = ['label' => $c->name];
	}

	$list = new DynamicList($columns, $table);
	$list->orderBy(key($columns), false);
	$list->setTitle($table);
	$list->loadFromQueryString();

	$tpl->assign(compact('table', 'list', 'is_module'));
}
elseif (qg('table_info') && array_key_exists(qg('table_info'), $tables_list)) {
	$name = qg('table_info');
	$info = $tables_list[$name];
	$info->schema = $db->getTableSchema($name);
	$info->indexes = $db->getTableIndexes($name);

	$sql_indexes = [];

	foreach ($info->indexes as $index) {
		$sql_indexes[] = $db->firstColumn('SELECT sql FROM sqlite_master WHERE type = \'index\' AND name = ?;', $index['name']);
	}

	$info->sql_indexes = implode(";\n", $sql_indexes);
	$tpl->assign('table_info', $info);
}
elseif (($pragma = qg('pragma')) || isset($query)) {
	try {
		$query_time = microtime(true);

		if ($pragma) {
			$query = '';
			$result = [];
			$result_header = null;

			if ($pragma == 'integrity_check') {
				$result = $db->get('PRAGMA integrity_check;');
			}
			elseif ($pragma == 'foreign_key_check') {
				$result = $db->get('PRAGMA foreign_key_check;') ?: [['no errors']];
			}
			elseif (ENABLE_TECH_DETAILS && $pragma == 'vacuum') {
				$result[] = ['Size before VACUUM: ' . Backup::getDBSize()];
				$db->exec('VACUUM;');
				$result[] = ['Size after VACUUM: ' . Backup::getDBSize()];
			}

			$result_count = count($result);
		}
		elseif (!empty($query)) {
			$s = Search::fromSQL($query);

			if (f('export')) {
				$s->export(f('export'), 'Requête SQL');
				return;
			}

			$result = $s->iterateResults();
			$result_header = $s->getHeader();
			$result_count = $s->countResults();
		}
		else {
			$result = $result_count = $result_header = null;
		}

		$query_time = round((microtime(true) - $query_time) * 1000, 3);

		$tpl->assign(compact('result', 'result_header', 'result_count', 'query_time'));
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}
else {
	foreach ($tables_list as $name => &$data) {
		$data->count = $db->count($name);
		$data->size = $db->getTableSize($name);
	}

	unset($data);

	$tpl->assign('index_list',$db->getAssoc('SELECT name, sql FROM sqlite_master WHERE type = \'index\' AND name NOT LIKE \'sqlite_%\' ORDER BY name;'));
	$tpl->assign('triggers_list', $db->getAssoc('SELECT name, sql FROM sqlite_master WHERE type = \'trigger\' ORDER BY name;'));
}

$tpl->assign(compact('tables_list', 'query', 'list'));

$tpl->register_modifier('format_json', function (string $str) {
	return json_encode(json_decode($str, true), JSON_PRETTY_PRINT);
});

$tpl->display('config/advanced/sql.tpl');
