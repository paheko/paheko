<?php
namespace Paheko;

use Paheko\Entities\Services\Reminder;
use Paheko\Services\Reminders;
use Paheko\Services\Services;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$reminder = Reminders::get((int) qg('id'));

if (!$reminder) {
	throw new UserException("Ce rappel n'existe pas");
}

$list = $reminder->sentList();
$list->loadFromQueryString();

$service = $reminder->service();

$tpl->assign(compact('list', 'reminder', 'service'));

$tpl->display('services/reminders/details.tpl');
