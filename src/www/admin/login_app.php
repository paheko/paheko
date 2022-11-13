<?php
namespace Garradin;

use Garradin\Files\Files;
use Garradin\Users\Session;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();

$app_token = $session->getAppLoginToken();

if (!$app_token) {
	die("No app token was supplied.");
}

$csrf_key = 'app_confirm_' . $app_token;

$form->runIf('cancel', function () {
	Utils::redirect('!logout.php');
});

$form->runIf('confirm', function () use ($app_token, $session) {
	if ($app_token == 'redirect') {
		$data = $session->createAppCredentials();
	}
	else {
		$session->validateAppToken($app_token);
	}

	if ($data->redirect ?? null) {
		http_response_code(303);
		header('Location: ' . $data->redirect);
		exit;
	}

	Utils::redirect('!login_app.php?app=ok');
}, $csrf_key);

$permissions = Files::listContextsPermissions($session);

$tpl->assign(compact('app_token', 'csrf_key', 'permissions'));

$tpl->display('login_app.tpl');
