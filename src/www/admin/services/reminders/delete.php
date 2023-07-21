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

$csrf_key = 'reminder_delete_' . $reminder->id();

$form->runIf('delete', function () use ($reminder) {
	$reminder->delete();
}, $csrf_key, ADMIN_URL . 'services/reminders/');

$tpl->assign(compact('reminder', 'csrf_key'));

$tpl->display('services/reminders/delete.tpl');
