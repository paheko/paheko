<?php
namespace Garradin;

use Garradin\Users\Emails;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

if ($address = qg('verify')) {
    $email = Emails::getEmail($address);

    if (!$email) {
        throw new UserException('Adresse invalide');
    }

    $csrf_key = 'send_verification';

    $form->runIf('send', function () use ($email, $address) {
        $email->sendVerification($address);
    }, $csrf_key, '!users/emails.php?sent', true);

    $tpl->assign(compact('csrf_key', 'email'));
    $tpl->display('users/emails_verification.tpl');
    exit;
}

$list = Emails::listRejectedUsers();
$list->loadFromQueryString();

$max_fail_count = Emails::FAIL_LIMIT;
$queue_count = Emails::countQueue();
$tpl->assign(compact('list', 'max_fail_count', 'queue_count'));

$tpl->display('users/emails.tpl');
