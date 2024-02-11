<?php
namespace Paheko;

use Paheko\Email\Queue;
use Paheko\Email\Emails;
use Paheko\Entities\Email\Message;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN);

$form->runIf(qg('run') && !USE_CRON, function () use ($session) {
	$i = (int) qg('run');

	Queue::run(100);

	$i++;

	// Continue sending by batch as long as there is something in the queue
	if ($i < 20 && Queue::count()) {
		Utils::redirect('!users/email/queue.php?run=' . $i);
	}
}, null, '!users/email/queue.php?msg=EMPTY');

$count = Queue::count();
$list = Queue::getList();
$statuses = Message::STATUS_LIST;
$statuses_colors = Message::STATUS_COLORS;
$contexts = Message::CONTEXT_LIST;

$tpl->assign(compact('list', 'count', 'statuses', 'statuses_colors', 'contexts'));

$tpl->display('users/email/queue.tpl');
