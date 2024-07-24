<?php

namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$user = Session::getLoggedUser();

$csrf_key = 'edit_otp_' . md5($user->password);

$form->runIf('confirm', function () use ($user, $session) {
	$user->importSecurityForm(true, null, $session);
	$user->save(false);
}, $csrf_key, '!me/security.php?ok');

$otp = null;

if ($edit == 'otp') {
	$otp = $session->getNewOTPSecret();
}

$tpl->assign(compact('id', 'edit', 'id_field', 'user', 'csrf_key', 'otp'));

$tpl->display('me/security.tpl');
