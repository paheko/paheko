<?php
namespace Paheko;

use Paheko\Users\Categories;
use Paheko\Users\DynamicFields;
use Paheko\Users\Users;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$csrf_key = 'users_new';
$user = Users::create();
$is_duplicate = null;

$form->runIf('save', function () use ($user, $session, &$is_duplicate) {
	$id_category = (int)f('id_category');

	if ($session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)) {
		$user->set('id_category', $id_category);
	}
	else {
		$user->setCategorySafe($id_category, $session);
	}

	$user->importForm();

	if (f('save') != 'anyway' && ($is_duplicate = $user->checkDuplicate())) {
		return;
	}

	$user->save();

	if (!empty($_GET['tab'])) {
		printf('<!DOCTYPE html><script type="text/javascript">window.parent.renameTabUser(%d, %s);</script>Redirection en cours...',
			$user->id(),
			json_encode($user->name())
		);
		exit;
	}

	Utils::redirect('!users/details.php?id=' . $user->id());
}, $csrf_key);


$tpl->assign('id_field_name', DynamicFields::getLoginField());

$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->assign('fields', DynamicFields::getInstance()->all());

$tpl->assign('categories', Categories::listAssocSafe($session));
$default_category = Config::getInstance()->default_category;
$tpl->assign('current_cat', f('id_category') ?: $default_category);

$tpl->assign(compact('user', 'default_category', 'csrf_key', 'is_duplicate'));

$tpl->display('users/new.tpl');
