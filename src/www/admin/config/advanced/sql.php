<?php
namespace Garradin;

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

	$columns = [];

	foreach ($all_columns as $c) {
		$columns[$c->name] = ['label' => $c->name];
	}

	$list = new DynamicList($columns, $table);
	$list->orderBy(key($columns), false);
	$list->loadFromQueryString();
	$tpl->assign(compact('table', 'list'));
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
elseif ($query) {
	try {
		$s = Search::fromSQL($query);
		$query_time = microtime(true);
		$result = $s->iterateResults();
		$query_time = round((microtime(true) - $query_time) * 1000, 3);
		$result_header = $s->getHeader();
		$result_count = $s->countResults();

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

$tpl->display('config/advanced/sql.tpl');
