<?php

namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$user = Session::getLoggedUser();

$csrf_key = 'edit_security_' . md5($user->password);
$edit = qg('edit');

$form->runIf('confirm', function () use ($user, $session) {
	$user->importSecurityForm(true, null, $session);
	$user->save();
}, $csrf_key, '!me/security.php?ok');

$otp = null;

if ($edit == 'otp') {
	$otp = $session->getNewOTPSecret();
}

$tpl->assign('can_use_pgp', \KD2\Security::canUseEncryption());
$tpl->assign('pgp_fingerprint', $user->pgp_key ? $session->getPGPFingerprint($user->pgp_key, true) : null);

$tpl->assign('ok', qg('ok') !== null);
$sessions_count = $session->countActiveSessions();

$id_field = current(DynamicFields::getInstance()->fieldsBySystemUse('login'));
$id = $user->{$id_field->name};
$can_change_password = $user->canChangePassword($session);

$tpl->assign(compact('id', 'edit', 'id_field', 'user', 'csrf_key', 'sessions_count', 'can_change_password', 'otp'));

$tpl->display('me/security.tpl');
