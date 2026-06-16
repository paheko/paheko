<?php

namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();
$user = $session->user();

$csrf_key = 'edit_password_' . md5($user->password);

$form->runIf('confirm', function () use ($user) {
	$user->setNewPassword($_POST, true);
	$user->save(false);
}, $csrf_key, '!me/security.php?ok');

$tpl->assign(compact('user', 'csrf_key'));

$tpl->display('me/security_password.tpl');
