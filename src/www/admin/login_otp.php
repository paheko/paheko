<?php

namespace Paheko;

use Paheko\Users\Session as UserSession;
use Paheko\Files\WebDAV\Session as AppSession;

use KD2\Form;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$app_token = Form::getQueryString('app');

if ($app_token) {
	$session = AppSession::getInstance();
}
else {
	$session = UserSession::getInstance();
}

if (!$session->isOTPRequired()) {
	Utils::redirect(ADMIN_URL);
}

if (Log::isOTPLocked()) {
	$session->logout();
	throw new UserException(sprintf("Vous avez dépassé la limite de tentatives de connexion.\nMerci d'attendre %d minutes avant de ré-essayer de vous connecter.", Log::LOCKOUT_DELAY/60));
}

$csrf_key = 'login_otp';

$args = $app_token ? '?app=' . rawurlencode($app_token) : '';
$layout = $app_token ? 'public' : null;

$form->runIf('login', function () use ($session, $args) {
	if (!$session->loginOTP(Form::getPostString('code', true) ?? '')) {
		throw new UserException(sprintf('Code incorrect. Vérifiez que votre téléphone est à l\'heure (heure du serveur : %s).', date('d/m/Y H:i:s')));
	}

	if ($args) {
		Utils::redirect('!login_app.php' . $args);
	}

	$url = Utils::getTrustedURL(Form::getQueryString('r'));
	$url ??= ADMIN_URL;
	Utils::redirect($url);
}, $csrf_key);

$tpl->assign(compact('csrf_key', 'layout'));

$tpl->display('login_otp.tpl');
