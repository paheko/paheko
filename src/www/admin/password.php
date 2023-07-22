<?php

namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();

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
	$session->recoverPasswordSend(f('id'));
}, $csrf_key, '!password.php?sent' . ($new ? '&new' : ''));

$sent = !$form->hasErrors() && null !== qg('sent');

$id_field = DynamicFields::get(DynamicFields::getLoginField());
$title = $new ? 'Première connexion ?' : 'Mot de passe perdu ?';

$tpl->assign(compact('id_field', 'sent', 'csrf_key', 'title', 'new'));

$tpl->display('password.tpl');
