<?php
namespace Paheko;

use Paheko\Users\DynamicFields;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'change_fields_order';
$fields = DynamicFields::getInstance();

$form->runIf('save', function () use ($fields) {
	$fields->setOrderAll(f('sort_order'));
	$fields->save();
}, $csrf_key, '!config/fields/?msg=SAVED_ORDER');

$tpl->assign('fields', $fields->all());
$tpl->assign(compact('csrf_key'));

$tpl->display('config/fields/index.tpl');
