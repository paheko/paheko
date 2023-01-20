<?php
namespace Garradin;

use KD2\ErrorManager;

require_once __DIR__ . '/../_inc.php';

$list = null;
$table = qg('table');
$query = f('query') ?? qg('query');

$db = DB::getInstance();
$tables_list = $db->getGrouped('SELECT name, sql, NULL AS count FROM sqlite_master WHERE type = \'table\' ORDER BY name;');
$index_list = null;
$triggers_list = null;

if ($table) {
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
	}

	unset($data);
	$index_list = $db->getAssoc('SELECT name, sql FROM sqlite_master WHERE type = \'index\' AND name NOT LIKE \'sqlite_%\' ORDER BY name;');
	$triggers_list = $db->getAssoc('SELECT name, sql FROM sqlite_master WHERE type = \'trigger\' ORDER BY name;');
}

$tpl->assign(compact('index_list', 'triggers_list', 'tables_list', 'query', 'table', 'list'));

$tpl->display('config/advanced/sql.tpl');
