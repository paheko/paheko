<?php
namespace Garradin;

use KD2\ErrorManager;

require_once __DIR__ . '/../_inc.php';

$list = null;
$table = qg('table');
$query = f('query');

$db = DB::getInstance();
$tables_list = $db->getGrouped('SELECT name, sql, NULL AS count FROM sqlite_master WHERE type = \'table\' ORDER BY name;');
$index_list = null;
$triggers_list = null;
$result = null;
$result_header = null;

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
		$result = Recherche::rawSQL($query);

		if (count($result)) {
			$result_header = array_keys((array)reset($result));
		}
	}
	catch (\Exception $e) {
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

$tpl->assign(compact('index_list', 'triggers_list', 'tables_list', 'query', 'table', 'list', 'result', 'result_header'));

$tpl->display('admin/config/advanced/sql.tpl');
