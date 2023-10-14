<?php
namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'edit_my_info';

$form->runIf('save', function () use ($user) {
	$user->importForm();
	$user->checkLoginFieldForUserEdit();
	$user->save();
}, $csrf_key, '!me/?ok');

$fields = DynamicFields::getInstance()->all();

$tpl->assign(compact('csrf_key', 'user', 'fields'));

$tpl->display('me/edit.tpl');
