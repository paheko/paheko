<?php
namespace Paheko;

use Paheko\Email\Addresses;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$raw_address = qg('address');
Addresses::validate($raw_address);

$address = Addresses::getOrCreate($raw_address);

if (!$address->canSendVerificationAfterFail()) {
	$message = sprintf('Il n\'est pas possible de renvoyer une vérification à cette adresse pour le moment, il faut attendre %d jours.', $address->getVerificationDelay());
    throw new UserException($message);
}

$csrf_key = 'send_verification';

$form->runIf('send', function () use ($address, $raw_address) {
    $address->sendVerification($raw_address);
}, $csrf_key, '!users/mailing/rejected.php?sent', true);

$tpl->assign(compact('csrf_key', 'address'));
$tpl->display('users/mailing/verify.tpl');
