<?php
namespace Paheko;

use Paheko\Email\Addresses;
use Paheko\Entities\Email\Address;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$limit_date = new \DateTime(sprintf('%d days ago', Email::RESEND_VERIFICATION_DELAY));

$status = $_GET['status'] ?? 'invalid';
$list = null;
$queue_count = null;
$type = null;

$labels = Address::STATUS_LIST;
$colors = Address::STATUS_COLORS;

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

$tpl->assign(compact('list', 'queue_count', 'limit_date', 'status', 'type', 'labels', 'colors'));

$tpl->display('users/mailing/status/index.tpl');
