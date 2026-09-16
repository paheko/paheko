<?php

namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();
$user = $session->user();

$csrf_key = 'edit_apps_' . md5($user->password);
$password = null;
$login = null;

$form->runIf('create', function () use ($user, &$password, &$login) {
	$password = $user->createAppPassword($_POST['name'] ?? '');
	$login = $user->login();
}, $csrf_key);

$form->runIf('delete', function () use ($user) {
	$user->deleteAppPassword((int) ($_POST['delete'] ?? 0));
}, $csrf_key, '!me/security_apps.php?msg=DELETED');

$list = $user->listAppPasswords();

$tpl->assign(compact('login', 'password', 'list', 'csrf_key'));

$tpl->display('me/security_apps.tpl');
