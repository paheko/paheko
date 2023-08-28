<?php
namespace Paheko;

use Paheko\Services\Reminders;
use Paheko\Services\Services;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$services_list = Services::listAssoc();

if (!count($services_list)) {
	Utils::redirect(ADMIN_URL . 'services/?CREATE');
}

$list = Reminders::list();

$tpl->assign(compact('list'));

$tpl->display('services/reminders/index.tpl');
