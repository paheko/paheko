<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

$csrf_key = 'acc_years_close_' . $year->id();

$form->runIf('close', function () use ($year, $user, $session) {
	$year->close($user->id);
	$year->save();

	$user = Session::getLoggedUser();

	// Year is closed, remove it from preferences
	if ($user->getPreference('accounting_year') == $year->id()) {
		$user->setPreference('accounting_year', null);
	}
	$session->save();
}, $csrf_key, ADMIN_URL . 'acc/years/new.php?from=' . $year->id());

$tpl->assign(compact('year', 'csrf_key'));

$tpl->display('acc/years/close.tpl');
