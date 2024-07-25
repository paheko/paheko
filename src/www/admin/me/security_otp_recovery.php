<?php

namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();
$user = $session->user();

if (!$user->canChangePassword($session)) {
	throw new UserException('Vous ne pouvez pas changer votre mot de passe.');
}

$csrf_key = 'edit_recovery_' . md5($user->password);
$generate = qg('generate') !== null;
$verified = false;

$form->runIf('generate', function () use ($user, &$verified, &$generate) {
	$user->verifyPassword(f('password_check'));
	$user->generateOTPRecoveryCodes();
	$user->save(false);
	$verified = true;
	$generate = false;
}, $csrf_key);

$form->runIf('verify', function () use ($user, &$verified) {
	$user->verifyPassword(f('password_check'));
	$verified = true;
}, $csrf_key);

$codes = $user->otp_recovery_codes ? implode("\n", $user->otp_recovery_codes) : null;

$tpl->assign(compact('user', 'csrf_key', 'generate', 'codes', 'verified'));

$tpl->display('me/security_otp_recovery.tpl');
