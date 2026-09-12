<?php
namespace Paheko;

use KD2\Form;
use KD2\HTTP;
use KD2\Security;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session as UserSession;
use Paheko\Files\WebDAV\Session as AppSession;

use Paheko\UserException;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$app_token = Form::getQueryString('app');

if ($app_token) {
	$session = AppSession::getInstance();
}
else {
	$session = UserSession::getInstance();
}

// Relance session_start et renvoie une image de 1px transparente
if (Form::getQueryString('keepSessionAlive') !== null)
{
	$session->keepAlive();

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

	header('Content-Type: image/gif');
	echo base64_decode("R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==");

	exit;
}

$args = $app_token ? '?app=' . rawurlencode($app_token) : '';
$layout = $app_token || Form::getQueryString('p') ? 'public' : null;

if (Form::getQueryString('r')) {
	$args .= ($args ? '&' : '?') . 'r=' . rawurlencode(Form::getQueryString('r'));
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
	$captcha_hash = Form::getPostString('c_hash') ?? '';
	$captcha_answer = Form::getPostString('c_answer', true) ?? '';

	if ($lock === 1) {
		throw new UserException(sprintf("Vous avez dépassé la limite de tentatives de connexion.\nMerci d'attendre %d minutes avant de ré-essayer de vous connecter.", Log::LOCKOUT_DELAY/60));
	}
	elseif ($lock === -1 && !$captcha_answer) {
		throw new UserException('Merci d\'entrer un code de vérification pour confirmer la connexion.');
	}
	elseif ($lock === -1 && !Security::checkCaptcha(LOCAL_SECRET_KEY, $captcha_hash, $captcha_answer)) {
		throw new UserException('Le code de vérification entré n\'est pas correct.');
	}

	// Make sure we don't pre-fill the answer form
	$_POST['c_answer'] = null;

	$id = Form::getPostString('id', true);
	$password = Form::getPostString('password', true);
	$permanent = (bool) Form::getPostBool('permanent');

	if (!$id) {
		throw new UserException(sprintf('L\'identifiant (%s) n\'a pas été renseigné.', $id_field_name));
	}

	if (!$password) {
		throw new UserException('Le mot de passe n\'a pas été renseigné.');
	}

	$ok = $session->login($id, $password, $permanent);

	if (!$ok) {
		throw new UserException(sprintf("Connexion impossible.\nVérifiez votre identifiant (%s) et votre mot de passe.", $id_field_name));
	}

	if ($session::REQUIRE_OTP === $ok) {
		Utils::redirect('!login_otp.php' . $args);
	}
	elseif ($app_token) {
		Utils::redirect('!login_app.php' . $args);
	}

	$url = Utils::getTrustedURL(Form::getQueryString('r'));
	$url ??= ADMIN_URL;
	Utils::redirect($url);
}, 'login');

$captcha = $lock == -1 ? Security::createCaptcha(LOCAL_SECRET_KEY, 'fr_FR') : null;

$ssl_enabled = HTTP::getScheme() == 'https';
$changed = Form::getQueryString('changed') !== null;
$redirect = Form::getQueryString('r');
$oidc_button = null;

if (OIDC_CLIENT_BUTTON && OIDC_CLIENT_URL) {
	$oidc_button = str_replace('%hostname%', parse_url(OIDC_CLIENT_URL, PHP_URL_HOST), OIDC_CLIENT_BUTTON);
}

$tpl->assign(compact('id_field', 'ssl_enabled', 'changed', 'app_token', 'layout', 'captcha', 'redirect', 'oidc_button'));

$tpl->display('login.tpl');
