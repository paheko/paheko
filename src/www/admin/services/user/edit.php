<?php
namespace Garradin;

use Garradin\Services\Services_User;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$su = Services_User::get((int) qg('id'));

if (!$su) {
	throw new UserException("Cette inscription n'existe pas");
}

$csrf_key = 'su_edit_' . $su->id();
$user_id = $su->id_user;
$user_name = (new Membres)->getNom($user_id);
$form_url = sprintf('edit.php?id=%d&', $su->id());

require __DIR__ . '/_form.php';

$form->runIf('save', function () use ($su) {
	$su->importForm();
	$su->save();
}, $csrf_key, ADMIN_URL . 'services/user/?id=' . $user_id);

$service_user = $su;

$tpl->assign(compact('csrf_key', 'service_user'));

$tpl->display('services/user/edit.tpl');
