<?php

namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$user = Session::getLoggedUser();

$can_use_pgp = \KD2\Security::canUseEncryption();
$pgp_fingerprint = $user->getPGPKeyFingerprint(null, true);

$tpl->assign('ok', qg('ok') !== null);
$sessions_count = $session->countActiveSessions();

$id_field = current(DynamicFields::getInstance()->fieldsBySystemUse('login'));
$id = $user->{$id_field->name};
$can_change_password = $user->canChangePassword($session);

$tpl->assign(compact('id', 'edit', 'id_field', 'user', 'csrf_key', 'sessions_count', 'can_change_password', 'otp', 'can_use_pgp', 'pgp_fingerprint'));

$tpl->display('me/security.tpl');
