<?php
namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\DynamicFields;
use Garradin\Entities\Users\User;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$csrf_key = 'users_new';
$default_category = Config::getInstance()->default_category;
$user = new User;
$is_duplicate = null;

$form->runIf('save', function () use ($default_category, $user, $session, &$is_duplicate) {
    $user->importForm();

	if (f('save') != 'anyway' && ($is_duplicate = $user->checkDuplicate())) {
		return;
	}

    if ($session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)) {
        $user->id_category = $default_category;
    }

    $user->save();
    Utils::redirect('!users/details.php?id=' . $user->id());
}, $csrf_key);


$tpl->assign('id_field_name', DynamicFields::getLoginField());

$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->assign('fields', DynamicFields::getInstance()->all());

$tpl->assign('categories', Categories::listSimple());
$tpl->assign('current_cat', f('id_category') ?: $default_category);

$tpl->assign(compact('user', 'default_category', 'csrf_key', 'is_duplicate'));

$tpl->display('users/new.tpl');
