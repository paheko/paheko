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

// Protection contre la modification des admins par des membres moins puissants
$category = $user->category();

if (($category->perm_users == $session::ACCESS_ADMIN)
	&& !$session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)) {
	throw new UserException("Seul un membre administrateur peut modifier un autre membre administrateur.");
}

$csrf_key = 'user_edit_' . $user->id;

$form->runIf('save', function () use ($user, $session) {
	$user->importForm();

	if (empty($_POST['id_parent'])) {
		$user->set('id_parent', null);
	}

	// Only admins can set a category
	if (f('id_category') && $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)) {
		$user->set('id_category', (int) f('id_category'));
	}

	$user->save();
}, $csrf_key, '!users/details.php?id=' . $user->id);

$categories = Categories::listAssoc();

$fields = DF::getInstance()->all();

$tpl->assign(compact('user', 'categories', 'fields', 'csrf_key'));

$tpl->display('users/edit.tpl');
