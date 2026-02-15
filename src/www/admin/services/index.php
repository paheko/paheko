<?php
namespace Paheko;

use Paheko\Entities\Services\Service;
use Paheko\Services\Services;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'service_add';

$form->runIf($session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN) && f('save'), function () {
	$service = new Service;
	$service->importForm();
	$service->save();
	Utils::redirect(ADMIN_URL . 'services/fees/?id=' . $service->id());
}, $csrf_key);

$has_archived_services = Services::hasArchivedServices();
$show_archived_services = $_GET['archived'] ?? false;

if ($show_archived_services) {
	$list = Services::listArchivedWithStats();
}
else {
	$list = Services::listWithStats();
}

$list->loadFromQueryString();

$tpl->assign(compact('csrf_key', 'has_archived_services', 'show_archived_services', 'list'));

$tpl->display('services/index.tpl');
