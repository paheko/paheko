<?php
namespace Paheko;

use Paheko\Email\Emails;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$address = qg('address');
$email = Emails::getOrCreateEmail($address);

if (!$email) {
    throw new UserException('Adresse invalide');
}

$csrf_key = 'block_email';

$form->runIf('send', function () use ($email, $address) {
    $email->setOptout('Désinscription manuelle par un administrateur');
    $email->save();
}, $csrf_key, '!users/');

$tpl->assign(compact('csrf_key', 'email', 'address'));
$tpl->display('users/mailing/block.tpl');
