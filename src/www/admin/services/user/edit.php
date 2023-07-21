<?php
namespace Paheko;

use Paheko\Services\Services_User;
use Paheko\Users\Users;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$su = Services_User::get((int) qg('id'));

if (!$su) {
	throw new UserException("Cette inscription n'existe pas");
}

$csrf_key = 'su_edit_' . $su->id();
$users = [$su->id_user => Users::getName($su->id_user)];
$form_url = sprintf('edit.php?id=%d&', $su->id());
$create = false;

require __DIR__ . '/_form.php';

$form->runIf('save', function () use ($su) {
	$su->importForm();
	$su->importForm(['paid' => (bool)f('paid')]);
	$su->updateExpectedAmount();
	$su->save();
}, $csrf_key, ADMIN_URL . 'services/user/?id=' . $su->id_user);

$service_user = $su;

$tpl->assign(compact('csrf_key', 'service_user'));

$tpl->display('services/user/edit.tpl');
