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

// Ne pas modifier le membre courant, on risque de se tirer une balle dans le pied
if ($user->id == $logged_user_id) {
	Utils::redirect('!me/security.php');
}

// Protection contre la modification des admins par des membres moins puissants
$category = $user->category();

if ($category->perm_users == $session::ACCESS_ADMIN
	&& !$session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)) {
	throw new UserException("Seul un membre administrateur peut modifier un autre membre administrateur.");
}

$csrf_key = 'user_security_' . $user->id;

$login_field = DF::getLoginField();

$form->runIf('save', function () use ($user) {
	$user->importSecurityForm(false);
	$user->save(false);
}, $csrf_key, '!users/details.php?id=' . $user->id);

$tpl->assign(compact('user', 'csrf_key', 'login_field'));

$tpl->display('users/edit_security.tpl');
