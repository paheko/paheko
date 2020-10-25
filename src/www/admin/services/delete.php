<?php
namespace Garradin;

use Garradin\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$service = Services::get((int) qg('id'));

if (!$service) {
	throw new UserException("Cette activité n'existe pas");
}

if (f('delete') && $form->check('service_delete_' . $service->id())) {
	try {
		$service->delete();
		Utils::redirect(ADMIN_URL . 'services/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign('service', $service);

$tpl->display('services/delete.tpl');
