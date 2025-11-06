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

// Verify e-mail address, and then display preferences to allow the user to change them
if (isset($_GET['y']) && $_GET['y'] !== 'ok') {
	if ($email->verify($_GET['y'])) {
		$email->save();
		Utils::redirect($email->getUserPreferencesURL() . '&y=ok');
	}
	else {
		throw new UserException('Impossible de vérifier cette adresse e-mail, le lien a expiré ou est invalide.');
	}
}

// Validate and save re-subscribe request from confirmation email
$form->runIf(isset($_GET['v'], $_GET['e']), function () use ($email, $hash) {
	if (!$email->confirmPreferences($_GET)) {
		throw new UserException('La requête est invalide, merci de recommencer.');
	}
}, null, '!email_preferences.php?saved&h=' . $hash);

$verified = !empty($_GET['y']);
$address_required = false;

// Save preferences
$form->runIf('save', function () use ($email, $verified, $hash, &$address_required) {
	$r = $email->savePreferencesFromUserForm($_POST, $verified);

	if ($r === 'confirmation_required') {
		$address_required = true;
		return;
	}

	Utils::redirect(sprintf('!email_preferences.php?%s&h=%s', $r, $hash));
}, $csrf_key);

if ($optout_context && !isset($_GET['saved'])) {
	$email->setOptout($optout_context);
}

$form_url = '?h=' . $hash;

$tpl->assign(compact('email', 'csrf_key', 'optout_context', 'verified', 'form_url', 'address_required'));

$tpl->display('email_preferences.tpl');
