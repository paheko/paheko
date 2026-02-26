<?php
namespace Paheko;

use Paheko\Email\Emails;
use Paheko\Entities\Email\Email;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$limit_date = new \DateTime(sprintf('%d days ago', Email::RESEND_VERIFICATION_DELAY));

$form->runIf(f('force_queue') && !USE_CRON, function () use ($session) {
	$session->requireAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN);

	Emails::runQueue();
}, null, '!./?forced');

$status = $_GET['status'] ?? 'invalid';
$list = null;
$queue_count = null;
$type = null;

if ($status === 'queue') {
	$queue_count = Emails::countQueue();
}
elseif ($status === 'optout') {
	$type = $_GET['type'] ?? 'mailings';
	$list = Emails::listOptoutUsers($type);
}
else {
	$list = Emails::listInvalidUsers();
	$status = 'invalid';
}

if (null !== $list) {
	$list->loadFromQueryString();
}

$tpl->assign(compact('list', 'queue_count', 'limit_date', 'status', 'type'));

$tpl->display('users/mailing/status/index.tpl');
