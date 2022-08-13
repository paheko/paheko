<?php

namespace Garradin;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();

$form->runIf(qg('c'), function () use ($session, $form) {
	if (!$session->recoverPasswordCheck(qg('c'))) {
		throw new UserException('Le lien que vous avez suivi est invalide ou a expiré.');
	}

	$csrf_key = 'password_change_' . qg('c');

	$form->runIf('change', function () use ($session) {
		$session->recoverPasswordChange(qg('c'), f('password'), f('password_confirmed'));
	}, $csrf_key, '!login.php?changed');

	$tpl->assign(compact('csrf_key'));
	$tpl->display('password_change.tpl');
	exit;
});

$csrf_key = 'recover_password';

$form->runIf('recover', function () use ($session) {
	$error = $session->recoverPasswordSend((int) f('id'));

	if ($error === 1) {
		throw new UserException('Aucun membre trouvé avec cette adresse e-mail, ou le membre trouvé n\'a pas le droit de se connecter.');
	}
	elseif ($error === 2) {
		throw new UserException('Ce membre n\'a pas le droit de se connecter.');
	}
}, $csrf_key, '!password.php?sent');

$sent = !$form->hasErrors() && null !== qg('sent');

$id_field = DynamicFields::get(DynamicFields::getLoginField());

$tpl->assign(compact('id_field', 'sent', 'csrf_key'));

$tpl->display('password.tpl');
