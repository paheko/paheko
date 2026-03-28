<?php
namespace Paheko;

require_once __DIR__ . '/../../_inc.php';

$db = DB::getInstance();
$tables_list = $db->getTablesList();

$tables = [];

foreach ($tables_list as $name => $data) {
	$tables[$name] = $db->getTableSchema($name);
}

$tpl->assign(compact('tables'));

$tpl->display('config/advanced/sql/diagram.tpl');
