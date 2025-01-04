<?php

namespace Paheko;

use Paheko\Users\Categories;
use Paheko\Users\DynamicFields as DF;
use Paheko\Users\Users;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$user = Users::get((int) qg('id'));

if (!$user) {
	throw new UserException("Ce membre n'existe pas.");
}

$logged_user_id = $session->getUser()->id;

// Don't edit password of current user directly, force them to use the correct form instead
// This is to make sure the user does not change their password without
// being able to remember it
if ($user->id === $logged_user_id) {
	Utils::redirect('!me/security.php');
}

// Protect against admin users being deleted/modified by less powerful users
$user->validateCanBeModifiedBy($session);
$user->validatePasswordCanBeChangedBy($session);

$csrf_key = 'user_security_' . $user->id;

$login_field = DF::getLoginField();

$form->runIf('save', function () use ($user) {
	if (f('password_delete')) {
		$user->deletePassword();
	}
	elseif (f('password')) {
		$user->setNewPassword(null, false);
	}

	if (f('otp_delete')) {
		$user->setOTPSecret(null);
	}

	if (f('pgp_delete')) {
		$user->setPGPKey(null);
	}

	$user->save(false);
}, $csrf_key, '!users/details.php?id=' . $user->id);

$tpl->assign(compact('user', 'csrf_key', 'login_field'));

$tpl->display('users/edit_security.tpl');
