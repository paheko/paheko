<?php
namespace Paheko;

use Paheko\Entities\Services\Reminder;
use Paheko\Services\Reminders;
use Paheko\Services\Services;

require_once __DIR__ . '/../_inc.php';

$user_id = (int) qg('id');
$list = Reminders::listSentForUser($user_id);

$tpl->assign(compact('list', 'user_id'));

$tpl->display('services/reminders/user.tpl');
