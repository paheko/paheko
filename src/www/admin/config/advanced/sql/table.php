<?php
namespace Paheko;

require_once __DIR__ . '/../../_inc.php';

$db = DB::getInstance();
$tables_list = $db->getTablesList();
$table = $_GET['name'] ?? '';

if (!array_key_exists($table, $tables_list)) {
	throw new UserException('This table does not exist');
}

$info = $tables_list[$table];
$info->schema = $db->getTableSchema($table);
$info->indexes = $db->getTableIndexes($table);

$sql_indexes = [];

foreach ($info->indexes as $index) {
	$sql_indexes[] = $db->firstColumn('SELECT sql FROM sqlite_master WHERE type = \'index\' AND name = ?;', $index['name']);
}

$info->sql_indexes = implode(";\n", $sql_indexes);
$tpl->assign(compact('info', 'table'));

$tpl->display('config/advanced/sql/table.tpl');
