<?php

namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;
use Paheko\Log;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();

if ($session->isLogged()) {
	Utils::redirect('!');
}

$form->runIf(qg('c') !== null, function () use ($session, $form, $tpl) {
	if (!$session->checkRecoveryPasswordQuery(qg('c'))) {
		throw new UserException('Le lien que vous avez suivi est invalide ou a expiré.');
	}

	$csrf_key = 'password_change_' . md5(qg('c'));

	$form->runIf('change', function () use ($session) {
		$session->recoverPasswordChange(qg('c'), f('password'), f('password_confirmed'));
	}, $csrf_key, '!login.php?changed');

	$tpl->assign(compact('csrf_key'));
	$tpl->display('password_change.tpl');
	exit;
});

$csrf_key = 'recover_password';
$new = qg('new') !== null;

$form->runIf('recover', function () use ($session) {
	if (Log::isPasswordRecoveryLocked()) {
		throw new UserException(sprintf("Vous avez dépassé la limite de demandes de récupération de mot de passe perdu.\nSi vous n'avez pas reçu l'e-mail de récupération de mot de passe, vérifiez votre dossier Spam ou indésirables.\nSinon merci d'attendre %d minutes avant de ré-essayer.", Log::LOCKOUT_DELAY/60));
	}

	$id = strval($_POST['id'] ?? '');

	if (trim($id) === '') {
		throw new UserException('Aucun identifiant fourni');
	}

	$session->recoverPasswordSend($id);
}, $csrf_key, '!password.php?sent' . ($new ? '&new' : ''));

$sent = !$form->hasErrors() && null !== qg('sent');

$id_field = DynamicFields::get(DynamicFields::getLoginField());
$title = $new ? 'Première connexion ?' : 'Mot de passe perdu ?';

$tpl->assign(compact('id_field', 'sent', 'csrf_key', 'title', 'new'));

$tpl->display('password.tpl');
