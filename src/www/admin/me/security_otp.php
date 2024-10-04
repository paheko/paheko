<?php

namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$user = Session::getLoggedUser();

$csrf_key = 'edit_otp_' . md5($user->password);

$form->runIf('disable', function () use ($user) {
	$user->setOTPSecret(null);
	$user->save(false); // Don't self-check other fields
}, $csrf_key, '!me/security.php?ok');

$form->runIf('enable', function () use ($user) {
	$user->verifyPassword(f('password_check'));
	$user->setOTPSecret(f('otp_secret'), f('otp_code'));
	$user->save(false); // Don't self-check other fields
}, $csrf_key, '!me/security.php?ok');

$otp = null;

if (!$user->otp_secret) {
	$otp = $user->createOTPSecret();
}

$tpl->assign(compact('user', 'csrf_key', 'otp'));

$tpl->display('me/security_otp.tpl');
