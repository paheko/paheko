<?php
namespace Paheko;

use KD2\HTTP;
use KD2\Security;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session as UserSession;
use Paheko\Files\WebDAV\Session as AppSession;

use Paheko\UserException;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$app_token = $_GET['app'] ?? null;

if ($app_token) {
	$session = AppSession::getInstance();
}
else {
	$session = UserSession::getInstance();
}

// Relance session_start et renvoie une image de 1px transparente
if (qg('keepSessionAlive') !== null)
{
	$session->keepAlive();

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

	header('Content-Type: image/gif');
	echo base64_decode("R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==");

	exit;
}

$args = $app_token ? '?app=' . rawurlencode($app_token) : '';
$layout = $app_token || qg('p') ? 'public' : null;

if (qg('r')) {
	$args .= ($args ? '&' : '?') . 'r=' . rawurlencode(qg('r'));
}

// L'utilisateur est déjà connecté
if ($session->isLogged()) {
	if ($app_token) {
		Utils::redirect('!login_app.php' . $args);
	}
	else {
		Utils::redirect(ADMIN_URL);
	}
}

$id_field = DynamicFields::get(DynamicFields::getLoginField());
$id_field_name = $id_field->label;
$lock = Log::isLocked();

$form->runIf(OIDC_CLIENT_URL && (isset($_GET['oidc']) || OIDC_CLIENT_BUTTON === null), function () use($session) {
	$session->loginOIDC();
	Utils::redirect(ADMIN_URL);
});

$form->runIf('login', function () use ($id_field_name, $session, $lock, $args, $app_token) {
	if ($lock == 1) {
		throw new UserException(sprintf("Vous avez dépassé la limite de tentatives de connexion.\nMerci d'attendre %d minutes avant de ré-essayer de vous connecter.", Log::LOCKOUT_DELAY/60));
	}
	elseif ($lock == -1 && !Security::checkCaptcha(LOCAL_SECRET_KEY, f('c_hash'), f('c_answer'))) {
		throw new UserException('Le code de vérification entré n\'est pas correct.');
	}

	$_POST['c_answer'] = null;

	if (!trim((string) f('id'))) {
		throw new UserException(sprintf('L\'identifiant (%s) n\'a pas été renseigné.', $id_field_name));
	}

	if (!trim((string) f('password'))) {
		throw new UserException('Le mot de passe n\'a pas été renseigné.');
	}

	$ok = $session->login(f('id'), f('password'), (bool) f('permanent'));

	if (!$ok) {
		throw new UserException(sprintf("Connexion impossible.\nVérifiez votre identifiant (%s) et votre mot de passe.", $id_field_name));
	}

	if ($session::REQUIRE_OTP === $ok) {
		Utils::redirect('!login_otp.php' . $args);
	}
	elseif ($app_token) {
		Utils::redirect('!login_app.php' . $args);
	}

	$url = Utils::getTrustedURL(qg('r'));
	$url ??= ADMIN_URL;
	Utils::redirect($url);
}, 'login');

$captcha = $lock == -1 ? Security::createCaptcha(LOCAL_SECRET_KEY, 'fr_FR') : null;

$ssl_enabled = HTTP::getScheme() == 'https';
$changed = qg('changed') !== null;
$redirect = qg('r');
$oidc_button = null;

if (OIDC_CLIENT_BUTTON && OIDC_CLIENT_URL) {
	$oidc_button = str_replace('%hostname%', parse_url(OIDC_CLIENT_URL, PHP_URL_HOST), OIDC_CLIENT_BUTTON);
}

$tpl->assign(compact('id_field', 'ssl_enabled', 'changed', 'app_token', 'layout', 'captcha', 'redirect', 'oidc_button'));

$tpl->display('login.tpl');
