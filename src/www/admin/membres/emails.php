<?php
namespace Garradin;

use Garradin\Users\Emails;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$limit_date = new \DateTime('1 month ago');

if ($address = qg('verify')) {
    $email = Emails::getEmail($address);

    if (!$email) {
        throw new UserException('Adresse invalide');
    }

    if ($email->last_sent > $limit_date && ($email->hasReachedFailLimit() || $email->invalid)) {
        throw new UserException('Il n\'est pas possible de renvoyer une vérification à cette adresse pour le moment, il faut attendre un mois.');
    }

    $csrf_key = 'send_verification';

    $form->runIf('send', function () use ($email, $address) {
        $email->sendVerification($address);
    }, $csrf_key, '!membres/emails.php?sent', true);

    $tpl->assign(compact('csrf_key', 'email'));
    $tpl->display('admin/membres/emails_verification.tpl');
    exit;
}

$list = Emails::listRejectedUsers();
$list->loadFromQueryString();

$max_fail_count = Emails::FAIL_LIMIT;
$queue_count = Emails::countQueue();
$tpl->assign(compact('list', 'max_fail_count', 'queue_count', 'limit_date'));

$tpl->display('admin/membres/emails.tpl');
