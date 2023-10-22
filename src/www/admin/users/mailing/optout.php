<?php
namespace Paheko;

use Paheko\Email\Mailings;
use Paheko\Email\Emails;
use Paheko\Entities\Email\Email;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$limit_date = new \DateTime(Email::RESEND_VERIFICATION_DELAY);

$list = Mailings::getOptoutUsersList();
$list->loadFromQueryString();

$tpl->assign(compact('list', 'limit_date'));

$tpl->display('users/mailing/optout.tpl');
