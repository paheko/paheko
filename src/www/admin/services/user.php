<?php
namespace Garradin;
use Garradin\Services\Services_User;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ACCES);

$user = (new Membres)->get((int) qg('id'));

if (!$user) {
	throw new UserException("Cet utilisateur est introuvable");
}

$form->runIf($session->canAccess('membres', Membres::DROIT_ECRITURE) && null !== qg('paid') && qg('su_id'), function () use ($user) {
	$su = Services_User::get((int) qg('su_id'));

	if (!$su) {
		throw new UserException("Cette inscription est introuvable");
	}

	$su->paid = (bool)qg('paid');
	$su->save();
}, null, ADMIN_URL . 'services/user.php?id=' . $user->id);

$list = Services_User::perUserList($user->id);
$list->loadFromQueryString();

$tpl->assign('services', Services_User::listDistinctForUser($user->id));
$tpl->assign(compact('list', 'user'));

$tpl->display('services/user.tpl');
