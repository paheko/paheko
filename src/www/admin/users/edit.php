<?php

namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\DynamicFields as DF;
use Garradin\Users\Users;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$user = Users::get((int) qg('id'));

if (!$user) {
	throw new UserException("Ce membre n'existe pas.");
}

$logged_user_id = $session->getUser()->id;

// Ne pas modifier le membre courant, on risque de se tirer une balle dans le pied
if ($user->id == $logged_user_id) {
	throw new UserException("Vous ne pouvez pas modifier votre propre profil, la modification doit être faite par un autre membre, pour éviter de vous empêcher de vous reconnecter.\nUtilisez la page 'Mes infos personnelles' pour modifier vos informations.");
}

// Protection contre la modification des admins par des membres moins puissants
$category = $user->category();

if (($category->perm_users == $session::ACCESS_ADMIN)
	&& ($session->getUser()->perm_users < $session::ACCESS_ADMIN)) {
	throw new UserException("Seul un membre administrateur peut modifier un autre membre administrateur.");
}

$csrf_key = 'user_edit_' . $user->id;

$form->runIf('save', function () use ($user, $logged_user_id, $session) {
	$user->importForm();

	if (empty($_POST['id_parent'])) {
		$user->id_parent = null;
	}

	// Only admins can set a category
	if (f('id_category') && $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)) {
		$user->id_category = f('id_category');
	}

	if (!empty(f('delete_password'))) {
		$user->password = null;
		$user->otp_secret = null;
		$user->pgp_key = null;
	}
	elseif (f('clear_otp')) {
		$user->otp_secret = null;
	}
	elseif (f('clear_pgp')) {
		$user->pgp_key = null;
	}

	$user->save();
}, $csrf_key, '!users/details.php?id=' . $user->id);

$login_field = DF::getLoginField();
$categories = Categories::listSimple();

$fields = DF::getInstance()->all();

$tpl->assign(compact('user', 'login_field', 'categories', 'fields', 'csrf_key'));

$tpl->display('users/edit.tpl');
