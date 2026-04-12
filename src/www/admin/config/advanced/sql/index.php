<?php
namespace Paheko;

require_once __DIR__ . '/../../_inc.php';

$db = DB::getInstance();
$tables_list = $db->getTablesList();
$filter = $_GET['filter'] ?? null;
$where = $filter ? sprintf(' AND tbl_name LIKE %s', $db->quote($filter . '%')) : '';

if ($filter) {
	foreach ($tables_list as $name => $date) {
		if (!str_starts_with($name, $filter)) {
			unset($tables_list[$name]);
		}
	}
}

foreach ($tables_list as $name => &$data) {
	$data->count = $db->count($name);
	$data->size = $db->getTableSize($name);
}

unset($data);

$index_list = $db->getAssoc(sprintf('SELECT name, sql FROM sqlite_master WHERE type = \'index\' AND name NOT LIKE \'sqlite_%%\' %s ORDER BY name;', $where));
$triggers_list = $db->getAssoc(sprintf('SELECT name, sql FROM sqlite_master WHERE type = \'trigger\' %s ORDER BY name;', $where));

$tpl->assign(compact('tables_list', 'index_list', 'triggers_list'));

$tpl->display('config/advanced/sql/index.tpl');
