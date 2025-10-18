<?php
namespace Paheko;

use Paheko\Email\Emails;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$address = qg('address');
$email = Emails::getOrCreateEmail($address);

if (!$email->canSendVerificationAfterFail()) {
	throw new UserException(sprintf('Il n\'est pas possible de renvoyer une vérification à cette adresse pour le moment, il faut attendre %d jours.', Email::RESEND_VERIFICATION_DELAY));
}

$csrf_key = 'send_verification';

$form->runIf('send', function () use ($email, $address) {
	$email->sendVerification($address);
}, $csrf_key, '!users/mailing/status/?status=invalid&sent', true);

$tpl->assign(compact('csrf_key', 'email'));
$tpl->display('users/mailing/status/verify.tpl');
