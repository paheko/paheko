<?php
namespace Paheko;

require_once __DIR__ . '/../../_inc.php';

$db = DB::getInstance();
$tables_list = $db->getTablesList();
$table = $_GET['name'] ?? '';

if (!array_key_exists($table, $tables_list)) {
	throw new UserException('This table does not exist');
}

$all_columns = $db->get(sprintf('PRAGMA table_info(%s);', $db->quoteIdentifier($table)));

if (!$all_columns) {
	throw new UserException('This table does not exist');
}

$columns = [];

foreach ($all_columns as $c) {
	$columns[$c->name] = ['label' => $c->name];
}

$foreign_keys = $db->getTableForeignKeys($table);

$list = new DynamicList($columns, $table);
$list->orderBy(key($columns), false);
$list->setTitle($table);
$list->loadFromQueryString();

if (!empty($_GET['only']) && is_array($_GET['only'])) {
	$list->setConditions(sprintf('%s = ?', $db->quoteIdentifier(key($_GET['only']))));
	$list->setParameter(0, current($_GET['only']));
}

$tpl->assign(compact('table', 'list', 'foreign_keys'));

$tpl->register_modifier('format_json', function (string $str) {
	return json_encode(json_decode($str, true), JSON_PRETTY_PRINT);
});

$tpl->display('config/advanced/sql/table_list.tpl');
