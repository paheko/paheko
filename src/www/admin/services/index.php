<?php
namespace Garradin;

use Garradin\Entities\Services\Service;
use Garradin\Services\Services;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'service_add';

$form->runIf($session->canAccess('membres', Membres::DROIT_ADMIN) && f('save'), function () {
	$service = new Service;
	$service->importForm();
	$service->save();
	Utils::redirect(ADMIN_URL . 'services/fees/?id=' . $service->id());
}, $csrf_key);

$tpl->assign(compact('csrf_key'));
$tpl->assign('list', Services::listWithStats());

$tpl->display('services/index.tpl');
