<?php
namespace Garradin;

use Garradin\Services\Services_User;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$su = Services_User::get((int) qg('id'));

if (!$su) {
	throw new UserException("Cette inscription n'existe pas");
}

$csrf_key = 'su_delete_' . $su->id();
$user_id = $su->id_user;

$form->runIf('delete', function () use ($su) {
	$su->delete();
}, $csrf_key, ADMIN_URL . 'services/user/?id=' . $user_id);

$user_name = (new Membres)->getNom($user_id);

$service_name = $su->service()->label;
$fee_name = $su->fee()->label;

$tpl->assign(compact('csrf_key', 'user_name', 'fee_name', 'service_name'));

$tpl->display('services/user/delete.tpl');
