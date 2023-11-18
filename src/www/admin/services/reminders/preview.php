<?php
namespace Paheko;

use Paheko\Services\Reminders;
use Paheko\Services\Services;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$reminder = Reminders::get((int) qg('id_reminder'));

if (!$reminder) {
	throw new UserException("Ce rappel n'existe pas");
}

$body = $reminder->getPreview((int) qg('id_user'));

$tpl->assign(compact('body'));

$tpl->display('services/reminders/preview.tpl');
