<?php

namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$user = Session::getLoggedUser();

if (!$user->password) {
	throw new UserException('You cannot change your security settings, as you don\'t have a password');
}

$can_use_pgp = \KD2\Security::canUseEncryption();
$pgp_fingerprint = $user->getPGPKeyFingerprint(null, true);

$tpl->assign('ok', qg('ok') !== null);
$sessions_count = $session->countActiveSessions();

$id_field = current(DynamicFields::getInstance()->fieldsBySystemUse('login'));
$id = $user->{$id_field->name};
$can_change_password = $user->canChangePassword($session);

$tpl->assign(compact('id', 'id_field', 'user', 'sessions_count', 'can_change_password', 'can_use_pgp', 'pgp_fingerprint'));

$tpl->display('me/security.tpl');
