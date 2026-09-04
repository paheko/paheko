<?php

namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;
use Paheko\Log;

use KD2\Form;
use KD2\Security;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();

if ($session->isLogged()) {
	Utils::redirect('!');
}

$code = Form::getQueryString('c');

$form->runIf($code !== null, function () use ($code, $session, $form, $tpl) {
	if (!$session->checkRecoveryPasswordQuery($code)) {
		throw new UserException('Le lien que vous avez suivi est invalide ou a expiré.');
	}

	$csrf_key = 'password_change_' . md5($code);

	$form->runIf('change', function () use ($session) {
		$session->recoverPasswordChange($code, Form::getPostString('password') ?? '', Form::getPostString('password_confirmed') ?? '');
	}, $csrf_key, '!login.php?changed');

	$tpl->assign(compact('csrf_key'));
	$tpl->display('password_change.tpl');
	exit;
});

$csrf_key = 'recover_password';
$new = isset($_GET['new']);
$lock = Log::isLocked();

$form->runIf('recover', function () use ($session, $lock) {
	$captcha_hash = Form::getPostString('c_hash') ?? '';
	$captcha_answer = Form::getPostString('c_answer', true) ?? '';

	if ($lock === 1) {
		throw new UserException(sprintf("Vous avez dépassé la limite de tentatives de récupération de mot de passe.\nMerci d'attendre %d minutes avant de ré-essayer.", Log::LOCKOUT_DELAY/60));
	}
	elseif ($lock === -1 && !Security::checkCaptcha(LOCAL_SECRET_KEY, $captcha_hash ?? '', $captcha_answer ?? '')) {
		throw new UserException('Le code de vérification entré n\'est pas correct.');
	}

	// Make sure we don't pre-fill the answer form
	$_POST['c_answer'] = null;

	if (Log::isPasswordRecoveryLocked()) {
		throw new UserException(sprintf("Vous avez dépassé la limite de demandes de récupération de mot de passe perdu.\nSi vous n'avez pas reçu l'e-mail de récupération de mot de passe, vérifiez votre dossier Spam ou indésirables.\nSinon merci d'attendre %d minutes avant de ré-essayer.", Log::LOCKOUT_DELAY/60));
	}

	$id = Form::getPostString('id', true);

	if ($id === '') {
		throw new UserException('Aucun identifiant fourni');
	}

	$session->recoverPasswordSend($id);
}, $csrf_key, '!password.php?sent' . ($new ? '&new' : ''));

$sent = !$form->hasErrors() && isset($_GET['sent']);

$id_field = DynamicFields::get(DynamicFields::getLoginField());
$title = $new ? 'Première connexion ?' : 'Mot de passe perdu ?';
$captcha = $lock == -1 ? Security::createCaptcha(LOCAL_SECRET_KEY, 'fr_FR') : null;

$tpl->assign(compact('id_field', 'sent', 'csrf_key', 'title', 'new', 'captcha'));

$tpl->display('password.tpl');
