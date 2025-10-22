<?php
namespace Paheko;

use Paheko\Email\Emails;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if (empty($_GET['un'])) {
	throw new UserException('Identifiant e-mail non fourni');
}

$code = $_GET['un'];
$email = Emails::getEmailFromQueryStringValue($code);
$csrf_key = 'email_preferences';

if (!$email) {
	throw new UserException('Adresse e-mail introuvable.');
}

$form->runIf(isset($_GET['h'], $_GET['e']), function () use ($email, $code) {
	if (!$email->confirmPreferences($_GET)) {
		throw new UserException('La requête est invalide, merci de recommencer.');
	}
}, null, '!email_preferences.php?saved&un=' . $code);

$form->runIf('validate', function () use ($email, $code) {
	$saved = $email->savePreferencesFromUserForm();

	if ($saved) {
		$keyword = 'saved';
	}
	else {
		$keyword = 'sent';
	}

	Utils::redirect(sprintf('!email_preferences.php?%s&un=%s', $keyword, $code));
}, $csrf_key);

$tpl->assign(compact('email', 'csrf_key'));

$tpl->display('email_preferences.tpl');
