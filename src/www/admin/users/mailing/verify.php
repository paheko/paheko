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

if (!$email->canSendVerificationAfterFail()) {
	if ($email->optout) {
		$message = 'Il n\'est pas possible de renvoyer une vérification à cette adresse pour le moment, il faut attendre 3 jours.';
	}
	else {
		$message = 'Il n\'est pas possible de renvoyer une vérification à cette adresse pour le moment, il faut attendre un mois.';
	}

    throw new UserException($message);
}

$csrf_key = 'send_verification';

$form->runIf('send', function () use ($email, $address) {
    $email->sendVerification($address);
}, $csrf_key, '!users/mailing/rejected.php?sent', true);

$tpl->assign(compact('csrf_key', 'email'));
$tpl->display('users/mailing/verify.tpl');
