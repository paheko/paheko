<?php

namespace Garradin;

use Garradin\Users\Session;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();

if (!$session->isOTPRequired()) {
	Utils::redirect(ADMIN_URL);
}

$login = null;
$csrf_key = 'login_otp';

$app_token = $session->getAppLoginToken();
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
