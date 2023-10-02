<?php
namespace Paheko;

use Paheko\Entities\Services\Reminder;
use Paheko\Services\Services;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$csrf_key = 'reminder_add';
$reminder = new Reminder;
$services_list = Services::listAssoc();

$form->runIf('save', function () use ($reminder) {
	$reminder->importForm();
	$reminder->save();
}, $csrf_key, '!services/reminders/');

$reminder->subject = $reminder::DEFAULT_SUBJECT;
$reminder->body = $reminder::DEFAULT_BODY;

$tpl->assign(compact('csrf_key', 'reminder', 'services_list'));

$tpl->display('services/reminders/new.tpl');
