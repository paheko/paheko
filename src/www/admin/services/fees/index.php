<?php
namespace Paheko;

use Paheko\Accounting\Projects;
use Paheko\Accounting\Years;
use Paheko\Entities\Services\Fee;
use Paheko\Services\Services;

require_once __DIR__ . '/../_inc.php';

$service = Services::get((int)qg('id'));

if (!$service) {
	throw new UserException("Cette activité n'existe pas");
}

$fees = $service->fees();

$form->runIf($session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN) && f('save'), function () use ($service) {
	$fee = new Fee;
	$fee->id_service = $service->id();
	$fee->importForm();
	$fee->save();
}, 'fee_add', ADMIN_URL . 'services/fees/?id=' . $service->id());

$accounting_enabled = false;
$years = Years::listOpen();

$tpl->assign(compact('service', 'accounting_enabled', 'years'));
$tpl->assign('list', $fees->listWithStats());
$tpl->assign('projects', Projects::listAssoc());

$tpl->display('services/fees/index.tpl');
