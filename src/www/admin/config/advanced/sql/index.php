<?php
namespace Paheko;

require_once __DIR__ . '/../../_inc.php';

$db = DB::getInstance();
$tables_list = $db->getTablesList();

foreach ($tables_list as $name => &$data) {
	$data->count = $db->count($name);
	$data->size = $db->getTableSize($name);
}

unset($data);

$tpl->assign('index_list',$db->getAssoc('SELECT name, sql FROM sqlite_master WHERE type = \'index\' AND name NOT LIKE \'sqlite_%\' ORDER BY name;'));
$tpl->assign('triggers_list', $db->getAssoc('SELECT name, sql FROM sqlite_master WHERE type = \'trigger\' ORDER BY name;'));

$tpl->assign(compact('tables_list'));

$tpl->display('config/advanced/sql/index.tpl');
