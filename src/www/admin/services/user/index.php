<?php
namespace Garradin;
use Garradin\Services\Services;
use Garradin\Services\Services_User;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_READ);

$user = (new Membres)->get((int) qg('id'));

if (!$user) {
	throw new UserException("Cet utilisateur est introuvable");
}

$form->runIf($session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE) && null !== qg('paid') && qg('su_id'), function () {
	$su = Services_User::get((int) qg('su_id'));

	if (!$su) {
		throw new UserException("Cette inscription est introuvable");
	}

	$su->paid = (bool)qg('paid');
	$su->save();
}, null, ADMIN_URL . 'services/user/?id=' . $user->id);

$only = (int)qg('only') ?: null;

$only_service = !$only ? null : Services::get($only);

$list = Services_User::perUserList($user->id, $only);
$list->setTitle(sprintf('Inscriptions — %s', $user->identite));
$list->loadFromQueryString();

$tpl->assign('services', Services_User::listDistinctForUser($user->id));
$tpl->assign(compact('list', 'user', 'only', 'only_service'));

$tpl->display('services/user/index.tpl');
