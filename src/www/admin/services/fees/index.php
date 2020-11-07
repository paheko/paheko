<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Accounting\Years;
use Garradin\Entities\Services\Fee;
use Garradin\Services\Services;

require_once __DIR__ . '/../_inc.php';

$service = Services::get((int)qg('id'));

if (!$service) {
	throw new UserException("Cette activité n'existe pas");
}

$fees = $service->fees();

$form->runIf($session->canAccess('membres', Membres::DROIT_ADMIN) && f('save'), function () use ($service) {
	$fee = new Fee;
	$fee->id_service = $service->id();
	$fee->importForm();
	$fee->save();
}, 'fee_add', ADMIN_URL . 'services/fees/?id=' . $service->id());

$targets = Account::TYPE_REVENUE;

$accounting_enabled = false;
$years = Years::listOpen();

$tpl->assign(compact('service', 'targets', 'accounting_enabled', 'years'));
$tpl->assign('list', $fees->listWithStats());

$tpl->display('services/fees/index.tpl');
