<?php
namespace Paheko;

use Paheko\Email\Emails;
use Paheko\Entities\Email\Email;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$limit_date = new \DateTime(Email::RESEND_VERIFICATION_DELAY);

$form->runIf(f('force_queue') && !USE_CRON, function () use ($session) {
	$session->requireAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN);

	Emails::runQueue();
}, null, '!./?forced');

$p = $_GET['p'] ?? null;
$list = null;
$queue_count = null;

if ($p === 'invalid') {
	$list = Emails::listInvalidUsers();
}
elseif ($p === 'optout') {
	$list = Emails::listOptoutUsers();
}
else {
	$queue_count = Emails::countQueue();
}

if (null !== $list) {
	$list->loadFromQueryString();
}

$max_fail_count = Emails::FAIL_LIMIT;
$tpl->assign(compact('list', 'max_fail_count', 'queue_count', 'limit_date', 'p'));

$tpl->display('users/mailing/status/index.tpl');
