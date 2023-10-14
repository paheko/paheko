<?php
namespace Paheko;

use Paheko\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$service = Services::get((int) qg('id'));

if (!$service) {
	throw new UserException("Cette activité n'existe pas");
}

$csrf_key = 'service_delete_' . $service->id();

$form->runIf('delete', function () use ($service) {
	if (!f('confirm_delete')) {
		throw new UserException('Merci de cocher la case pour confirmer la suppression.');
	}

	$service->delete();
}, $csrf_key, ADMIN_URL . 'services/');

$tpl->assign(compact('service', 'csrf_key'));

$tpl->display('services/delete.tpl');
