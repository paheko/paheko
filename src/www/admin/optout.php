<?php
namespace Garradin;

use Garradin\Users\Emails;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if (empty($_GET['code'])) {
	throw new UserException('Demande de désinscription incomplète.');
}

$email = Emails::getEmailEntityFromOptout($_GET['code']);

if (!$email) {
	throw new UserException('Adresse email introuvable.');
}

// RFC 8058
if (!empty($_POST['Unsubscribe']) && $_POST['Unsubscribe'] == 'Yes') {
	$email->setOptout();
	$email->save();
	http_response_code(200);
	echo 'Unsubscribe successful';
	exit;
}

$form->runIf('confirm_resub', function () use ($email) {
	$email->sendVerification();
}, 'optout', '!optout.php?resub=ok');

$form->runIf('optout', function () use ($email) {
	$email->setOptout();
}, 'optout', '!optout.php?ok');

$ok = isset($_GET['ok']);
$resub_ok = isset($_GET['resub']);

$tpl->assign(compact('email', 'ok', 'resub_ok'));

$tpl->display('admin/optout.tpl');
