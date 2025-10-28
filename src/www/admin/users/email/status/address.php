<?php
namespace Paheko;

use Paheko\Email\Addresses;
use Paheko\Entities\Email\Address;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_READ);

$address = Addresses::getByID((int)qg('id'));

if (!$address) {
	throw new UserException('Adresse e-mail inconnue');
}

$limit_date = Addresses::getVerificationLimitDate();
$max_fail_count = Address::FAIL_LIMIT;

$tpl->assign(compact('address', 'max_fail_count', 'limit_date'));

$tpl->display('users/email/address.tpl');
