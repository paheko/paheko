<?php
namespace Paheko;

use Paheko\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$service = Services::get((int) qg('id'));

if (!$service) {
	throw new UserException("Cette activité n'existe pas");
}

$csrf_key = 'service_edit_' . $service->id();

$form->runIf('save', function () use ($service) {
	$service->importForm();
	$service->save();
}, $csrf_key, ADMIN_URL . 'services/');

if ($service->duration) {
	$period = 1;
}
elseif ($service->start_date) {
	$period = 2;
}
else {
	$period = 0;
}

$tpl->assign(compact('service', 'period', 'csrf_key'));

$tpl->display('services/edit.tpl');
