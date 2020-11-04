<?php
namespace Garradin;

use Garradin\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$service = Services::get((int) qg('id'));

if (!$service) {
	throw new UserException("Cette activité n'existe pas");
}

$csrf_key = 'service_delete_' . $service->id();

$form->runIf('delete', function () use ($service) {
	$service->delete();
}, $csrf_key, ADMIN_URL . 'services/');

$tpl->assign(compact('service', 'csrf_key'));

$tpl->display('services/delete.tpl');
