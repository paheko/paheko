<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Install;

require_once __DIR__ . '/../_inc.php';

$user = Session::getLoggedUser();

if (empty($user->password)) {
	throw new UserException('Votre compte ne dispose pas de mot de passe, cette fonctionnalité est désactivée.');
}

$form->runIf('reset_ok', function () use ($session) {
	Install::reset($session, f('password_check') ?? '');
}, 'reset');

$tpl->display('config/advanced/reset.tpl');
