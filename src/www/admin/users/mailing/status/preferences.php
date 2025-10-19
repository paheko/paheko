<?php
namespace Paheko;

use Paheko\Email\Emails;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$address = qg('address');
$email = Emails::getOrCreateEmail($address);

$csrf_key = 'block_email';

$form->runIf('send', function () use ($email) {
	$email->adminSetPreferences();
    $email->save();
}, $csrf_key, '!users/');

$user_prefs_url = $email->getUserPreferencesURL();

$tpl->assign(compact('csrf_key', 'email', 'address', 'user_prefs_url'));
$tpl->display('users/mailing/status/preferences.tpl');
