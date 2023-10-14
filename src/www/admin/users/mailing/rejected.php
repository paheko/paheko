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
}, null, '!membres/emails.php?forced');

$list = Emails::listRejectedUsers();
$list->loadFromQueryString();

$max_fail_count = Emails::FAIL_LIMIT;
$queue_count = Emails::countQueue();
$tpl->assign(compact('list', 'max_fail_count', 'queue_count', 'limit_date'));

$tpl->display('users/mailing/rejected.tpl');
