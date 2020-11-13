<?php
namespace Garradin;

use Garradin\Services\Services_User;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

$su = Services_User::get((int) qg('id'));

if (!$su) {
	throw new UserException("Cette inscription n'existe pas");
}

$csrf_key = 'su_delete_' . $su->id();
$user_id = $su->id_user;

$form->runIf('delete', function () use ($su) {
	$su->delete();
}, $csrf_key, ADMIN_URL . 'services/user.php?id=' . $user_id);

$tpl->assign(compact('csrf_key'));

$tpl->display('services/user_delete.tpl');
