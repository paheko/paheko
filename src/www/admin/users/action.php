<?php
namespace Paheko;

use Paheko\Users\Categories;
use Paheko\Users\Export;
use Paheko\Users\Users;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

Session::getInstance()->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

if (!f('selected') || !is_array(f('selected')) || !count(f('selected'))) {
	throw new UserException("Aucun membre sélectionné.");
}

$action = f('action');
$list = f('selected');
$list = array_map('intval', $list);
$csrf_key = 'users_actions';

if ($action === 'ods' || $action === 'csv' || $action === 'xlsx') {
	Export::exportSelected($action, $list);
	return;
}
elseif ($action === 'subscribe') {
	Utils::redirect('!services/subscription/new.php?users=' . implode(',', $list));
}
elseif ($action === 'move' || $action === 'delete' || $action === 'delete_files') {
	Session::getInstance()->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

	$logged_user_id = Session::getUserId();

	// Don't allow to change or delete the currently logged-in user
	// to avoid shooting yourself in the foot
	$list = array_filter($list, fn ($a) => $a != $logged_user_id);
}
else {
	throw new UserException('Action invalide');
}

if ($action == 'move') {
	$form->runIf('confirm', function () use ($list) {
		Users::changeCategorySelected((int)f('new_category_id'), $list, Session::getInstance());
	}, $csrf_key, '!users/?msg=CATEGORY_CHANGED');

	$tpl->assign('categories', Categories::listAssocSafe($session));
}
elseif ($action == 'delete') {
	$form->runIf('delete', function () use ($list) {
		Users::deleteSelected($list);
	}, $csrf_key, '!users/?msg=DELETE_MULTI');

	$tpl->assign('extra', ['selected' => $list, 'action' => $action]);
}
elseif ($action == 'delete_files') {
	$form->runIf('delete', function () use ($list) {
		Users::deleteFilesSelected($list);
	}, $csrf_key, '!users/?msg=DELETE_FILES');

	$tpl->assign('extra', ['selected' => $list, 'action' => $action]);
}

$count = count($list);
$tpl->assign(compact('list', 'count', 'action', 'csrf_key'));

$tpl->display('users/action.tpl');
