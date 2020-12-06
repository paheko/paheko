<?php
namespace Garradin;

use Garradin\Entities\Services\Reminder;
use Garradin\Services\Reminders;
use Garradin\Services\Services;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$reminder = Reminders::get((int) qg('id'));

if (!$reminder) {
	throw new UserException("Ce rappel n'existe pas");
}

$list = $reminder->sentList();
$list->loadFromQueryString();

$service = $reminder->service();

$tpl->assign(compact('list', 'reminder', 'service'));

$tpl->display('services/reminders/details.tpl');
