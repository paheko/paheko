<?php
namespace Paheko;

use Paheko\Email\Addresses;
use Paheko\Entities\Email\Address;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$list = Addresses::listRejectedUsers();
$list->loadFromQueryString();

$labels = Address::STATUS_LIST;
$colors = Address::STATUS_COLORS;

$tpl->assign(compact('list', 'labels', 'colors'));

$tpl->display('users/email/rejected.tpl');
