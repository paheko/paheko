<?php
namespace Paheko;

use Paheko\Email\Emails;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$address = qg('address');
$email = Emails::getOrCreateEmail($address);

$csrf_key = 'send_verification';

$form->runIf('send', function () use ($email, $address) {
    $email->sendVerification($address);
}, $csrf_key, '!users/mailing/rejected.php?sent', true);

$tpl->assign(compact('csrf_key', 'email'));
$tpl->display('users/mailing/verify.tpl');
