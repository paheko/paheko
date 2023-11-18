<?php
namespace Paheko;

use Paheko\Services\Reminders;
use Paheko\Services\Services;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$reminder = Reminders::get((int) qg('id'));

if (!$reminder) {
	throw new UserException("Ce rappel n'existe pas");
}

$service = $reminder->service();

if (qg('list') === 'pending') {
	$current_list = 'pending';
	$list = $reminder->pendingList();
}
else {
	$current_list = 'sent';
	$list = $reminder->sentList();
}

$list->loadFromQueryString();

$tpl->assign(compact('current_list', 'list', 'reminder', 'service'));

$tpl->display('services/reminders/details.tpl');
