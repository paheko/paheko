<?php
namespace Paheko;

use Paheko\Email\Queue;
use Paheko\Email\Emails;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN);

$form->runIf(qg('run') && !USE_CRON, function () use ($session) {
	$i = (int) qg('run');

	Queue::run(100);

	$i++;

	// Continue sending by batch as long as there is something in the queue
	if ($i < 20 && Queue::count()) {
		Utils::redirect('!users/mailing/queue.php?run=' . $i);
	}
}, null, '!users/mailing/queue.php?msg=EMPTY');

$count = Queue::count();
$list = Queue::getList();
$statuses = Queue::getStatusList();
$statuses_colors = Queue::getStatusColors();
$contexts = Emails::getContextsList();

$tpl->assign(compact('list', 'count', 'statuses', 'statuses_colors', 'contexts'));

$tpl->display('users/mailing/queue.tpl');
