<?php
namespace Paheko;

use Paheko\Email\Emails;

const LOGIN_PROCESS = true;

require_once __DIR__ . '/_inc.php';

if (empty($_GET['h'])) {
	throw new UserException('Identifiant e-mail non fourni');
}

$hash = $_GET['h'];
$optout_context = $_GET['c'] ?? null;

$email = Emails::getEmailFromQueryStringValue($hash);
$csrf_key = 'email_preferences';

if (!$email) {
	throw new UserException('Adresse e-mail introuvable.');
}

// Validate and save re-subscribe request
$form->runIf(isset($_GET['v'], $_GET['e']), function () use ($email, $hash) {
	if (!$email->confirmPreferences($_GET)) {
		throw new UserException('La requête est invalide, merci de recommencer.');
	}
}, null, '!email_preferences.php?saved&h=' . $hash);

// Save preferences, if user is coming from optout link, then don't require double opt-in
$form->runIf('validate', function () use ($email, $hash, $optout_context) {
	$saved = $email->savePreferencesFromUserForm($_POST, $optout_context);

	if ($saved) {
		$keyword = 'saved';
	}
	else {
		$keyword = 'sent';
	}

	Utils::redirect(sprintf('!email_preferences.php?%s&h=%s&c=%d', $keyword, $hash, $optout_context));
}, $csrf_key);

if ($optout_context && !isset($_GET['saved'])) {
	$email->setOptout($optout_context);
}

$tpl->assign(compact('email', 'csrf_key', 'optout_context'));

$tpl->display('email_preferences.tpl');
