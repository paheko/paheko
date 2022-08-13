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

$form->runIf('login', function () use ($session) {
	if (!$session->loginOTP(f('code'))) {
		throw new UserException(sprintf('Code incorrect. Vérifiez que votre téléphone est à l\'heure (heure du serveur : %s).', date('d/m/Y H:i:s')));
	}
}, $csrf_key, '!');

$tpl->assign(compact('csrf_key'));

$tpl->display('login_otp.tpl');
