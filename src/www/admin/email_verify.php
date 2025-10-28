<?php
namespace Paheko;

use Paheko\Email\Addresses;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if (empty($_GET['h']) || empty($_GET['y'])) {
	throw new UserException('Demande de vérification incomplète.');
}

$email = Emails::getEmailFromQueryStringValue($_GET['h']);

if (!$email) {
	throw new UserException('Adresse email inconnue.');
}

if ($email->verify($_GET['y'])) {
	$email->save();
	$verify = true;
}
else {
	$verify = false;
}

$tpl->assign(compact('verify'));

$tpl->display('email_verify.tpl');
