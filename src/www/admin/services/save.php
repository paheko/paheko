<?php
namespace Garradin;

use Garradin\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

$user_id = (int) qg('user') ?: null;
$user_name = $user_id ? (new Membres)->getNom($user_id) : null;

$grouped_services = Services::listGroupedWithFees($user_id);

$csrf_key = 'service_save';

$form->runIf('save', function () {
	$service->importForm();
	$service->save();
}, $csrf_key, ADMIN_URL . 'services/');

$selected_user = $user_id ? [$user_id => $user_name] : null;

$tpl->assign(compact('grouped_services', 'csrf_key', 'selected_user'));

$tpl->display('services/save.tpl');
