<?php
namespace Paheko;

use Paheko\Email\Emails;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if (empty($_GET['un'])) {
	throw new UserException('Demande de désinscription incomplète.');
}

$code = $_GET['un'];
$email = Emails::getEmailFromOptout($code);
$verify = null;

if (!$email) {
	throw new UserException('Adresse email introuvable.');
}

if (!empty($_GET['v'])) {
	if ($email->verify($_GET['v'])) {
		$email->save();
		$verify = true;
	}
	else {
		$verify = false;
	}
}

$form->runIf('confirm_resub', function () use ($email) {
	if (empty($_POST['email'])) {
		throw new UserException('Merci de renseigner l\'adresse email');
	}

	$email->sendVerification($_POST['email']);
}, 'optout', '!optout.php?resub_ok&un=' . $code);

$form->runIf('optout', function () use ($email) {
	$email->setOptout();
	$email->save();
}, 'optout', '!optout.php?ok&un=' . $code);

$ok = isset($_GET['ok']);
$resub_ok = isset($_GET['resub_ok']);

$tpl->assign(compact('email', 'ok', 'resub_ok', 'code', 'verify'));

$tpl->display('optout.tpl');
