<?php

namespace Paheko;

use Paheko\Users\Session as UserSession;
use Paheko\Files\WebDAV\Session as AppSession;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$app_token = $_GET['app'] ?? null;

if ($app_token) {
	$session = AppSession::getInstance();
}
else {
	$session = UserSession::getInstance();
}

if (!$session->isOTPRequired()) {
	Utils::redirect(ADMIN_URL);
}

$login = null;
$csrf_key = 'login_otp';

$args = $app_token ? '?app=' . rawurlencode($app_token) : '';
$layout = $app_token ? 'public' : null;

$form->runIf('login', function () use ($session, $args) {
	if (!$session->loginOTP(f('code'))) {
		throw new UserException(sprintf('Code incorrect. Vérifiez que votre téléphone est à l\'heure (heure du serveur : %s).', date('d/m/Y H:i:s')));
	}

	if ($args) {
		Utils::redirect('!login_app.php' . $args);
	}
}, $csrf_key, '!');

$tpl->assign(compact('csrf_key', 'layout'));

$tpl->display('login_otp.tpl');
