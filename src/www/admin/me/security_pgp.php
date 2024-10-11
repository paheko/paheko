<?php

namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

if (!\KD2\Security::canUseEncryption()) {
	throw new UserException('PGP support is missing on this server.');
}

$user = Session::getLoggedUser();

$csrf_key = 'edit_pgp_' . md5($user->password);

$form->runIf('confirm', function () use ($user) {
	$user->verifyPassword(f('password_check'));
	$user->setPGPKey($user->pgp_key ? null : f('pgp_key'));
	$user->save(false);
}, $csrf_key, '!me/security.php?ok');

$tpl->assign(compact('user', 'csrf_key'));

$tpl->display('me/security_pgp.tpl');
