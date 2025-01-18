<?php

namespace Paheko;

use Paheko\Services\Services;
use Paheko\Services\Services_User;
use Paheko\Users\Users;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_READ);

$user_id = (int) qg('id');
$user = Users::get($user_id);

if (!$user) {
	throw new UserException("Ce membre est introuvable");
}

$form->runIf($session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE) && null !== qg('paid') && qg('su_id'), function () {
	$su = Services_User::get((int) qg('su_id'));

	if (!$su) {
		throw new UserException("Cette inscription est introuvable");
	}

	$su->paid = (bool)qg('paid');
	$su->save();
}, null, ADMIN_URL . 'services/user/?id=' . $user_id);

$only = (int)qg('only') ?: null;

if ($after = qg('after')) {
	$after = \DateTime::createFromFormat('!Y-m-d', $after) ?: null;
}

$only_service = !$only ? null : Services::get($only);
$user_name = $user->name();

$list = Services_User::perUserList($user_id, $only, $after);
$list->setTitle(sprintf('Inscriptions — %s', $user_name));
$list->loadFromQueryString();

$tpl->assign('services', Services_User::listDistinctForUser($user_id));
$tpl->assign(compact('list', 'user_id', 'user_name', 'user', 'only', 'only_service', 'after'));

$tpl->display('services/user/index.tpl');
