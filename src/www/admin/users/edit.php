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

// Protect against admin users being deleted/modified by less powerful users
$user->validateCanChange($session);

$categories = Categories::listAssocSafe($session);
$csrf_key = 'user_edit_' . $user->id;
$can_change_category = array_key_exists($user->id_category, $categories);

$list_category = isset($_GET['list_category']) && strlen($_GET['list_category']) ? intval($_GET['list_category']) : null;

$form->runIf('save', function () use ($user, $session, $can_change_category, $list_category) {
	$user->importForm();
	$myself = $user->id == $session::getUserId();

	if (empty($_POST['id_parent'])) {
		$user->set('id_parent', null);
	}

	if ($can_change_category && !($session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN) && $myself)) {
		$user->setCategorySafe((int) f('id_category'), $session);
	}

	$user->save();

	if ($myself) {
		$session->refresh();

		// If user has removed their own rights to access users, redirect to admin homepage
		if (!$session->canAccess($session::SECTION_USERS, $session::ACCESS_READ)) {
			Utils::redirect('!');
		}
	}

	// Handle case where user id_category was changed
	if ($list_category > 0) {
		$list_category = $user->id_category;
	}
}, $csrf_key, sprintf('!users/details.php?id=%d&list_category=%s', $user->id, $list_category));

$fields = DF::getInstance()->all();

$tpl->assign(compact('list_category', 'user', 'categories', 'fields', 'csrf_key', 'can_change_category'));

$tpl->display('users/edit.tpl');
