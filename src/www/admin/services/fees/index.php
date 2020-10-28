<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Services\Fee;
use Garradin\Services\Services;

require_once __DIR__ . '/../_inc.php';

$service = Services::get((int)qg('id'));

if (!$service) {
    throw new UserException("Cette activité n'existe pas");
}

$fees = $service->fees();

if ($session->canAccess('membres', Membres::DROIT_ADMIN) && f('add') && $form->check('fee_new')) {
	try {
		$fee = new Fee;
		$fee->id_service = $service->id();
		$fee->importForm();
		$fee->save();

		Utils::redirect(ADMIN_URL . 'services/fees/?id=' . $service->id());
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$targets = Account::TYPE_REVENUE;

$tpl->assign(compact('service', 'targets'));
$tpl->assign('list', $fees->listWithStats());

$tpl->display('services/fees/index.tpl');
