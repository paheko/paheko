<?php
namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

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
	$session->set('acc_year', null);
}, $csrf_key, ADMIN_URL . 'acc/years/');

$tpl->assign(compact('year', 'csrf_key'));

$tpl->display('acc/years/close.tpl');
