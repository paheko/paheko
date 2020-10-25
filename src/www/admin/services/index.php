<?php
namespace Garradin;

use Garradin\Entities\Services\Service;
use Garradin\Services\Services;

require_once __DIR__ . '/_inc.php';

if ($session->canAccess('membres', Membres::DROIT_ADMIN) && f('add') && $form->check('service_new')) {
	try {
		$service = new Service;
		$service->importForm();
		$service->save();

		Utils::redirect(ADMIN_URL . 'services/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign('list', Services::listWithStats());

$tpl->display('services/index.tpl');
