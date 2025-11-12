<?php
namespace Paheko;

use Paheko\Email\Emails;
use Paheko\Entities\Email\Email;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$address = qg('address');
$email = Emails::getEmail($address);

if (!$email) {
	throw new UserException('Adresse invalide');
}

$max_fail_count = Email::FAIL_LIMIT;

$tpl->assign(compact('email', 'address', 'max_fail_count'));
$tpl->display('users/mailing/status/address.tpl');
