<?php
namespace Paheko;

use Paheko\Email\Addresses;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$address = qg('address');
$email = Addresses::get($address);

if (!$email) {
	throw new UserException('Adresse invalide ou inconnue');
}

$csrf_key = 'email_preferences_' . $email->hash;

$form->runIf('send', function () use ($email) {
	$email->adminSetPreferences();
	$email->save();
}, $csrf_key, '!users/');

$user_prefs_url = $email->getUserPreferencesURL();

$tpl->assign(compact('csrf_key', 'email', 'address', 'user_prefs_url'));
$tpl->display('users/mailing/status/preferences.tpl');
