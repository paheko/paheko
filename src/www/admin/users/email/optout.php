<?php
namespace Paheko;

use Paheko\Email\Mailings;
use Paheko\Email\Addresses;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$limit_date = Addresses::getVerificationLimitDate();

$list = Mailings::getOptoutUsersList();
$list->loadFromQueryString();

$tpl->assign(compact('list', 'limit_date'));

$tpl->display('users/email/optout.tpl');
