<?php
namespace Garradin;

use Garradin\Email\Emails;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$limit_date = new \DateTime('1 month ago');

if ($address = qg('verify')) {
    $email = Emails::getOrCreateEmail($address);

    if (!$email) {
        throw new UserException('Adresse invalide');
    }

    if ($email->last_sent > $limit_date && ($email->hasReachedFailLimit() || $email->invalid)) {
        throw new UserException('Il n\'est pas possible de renvoyer une vérification à cette adresse pour le moment, il faut attendre un mois.');
    }

    $csrf_key = 'send_verification';

    $form->runIf('send', function () use ($email, $address) {
        $email->sendVerification($address);
    }, $csrf_key, '!users/emails.php?sent', true);

    $tpl->assign(compact('csrf_key', 'email'));
    $tpl->display('users/emails_verification.tpl');
    exit;
}

$form->runIf(f('force_queue') && !USE_CRON, function () use ($session) {
    $session->requireAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN);

    Emails::runQueue();
}, null, '!membres/emails.php?forced');

$list = Emails::listRejectedUsers();
$list->loadFromQueryString();

$max_fail_count = Emails::FAIL_LIMIT;
$queue_count = Emails::countQueue();
$tpl->assign(compact('list', 'max_fail_count', 'queue_count', 'limit_date'));

$tpl->display('users/emails.tpl');
