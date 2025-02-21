<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

if (!$session->canAccess(Session::SECTION_CONFIG, Session::ACCESS_ADMIN)) {
	throw new UserException('Seul un administrateur ayant accès à la configuration peut réouvrir un exercice.');
}

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if (!$year->isClosed()) {
	throw new UserException('Cet exercice n\'est pas clôturé.');
}

$csrf_key = 'reopen_' . $year->id();

$form->runIf('reopen', function () use ($year) {
	if (!boolval($_POST['confirm'] ?? false)) {
		throw new UserException('Merci de cocher la case pour confirmer la réouverture.');
	}

	$year->reopen(Session::getUserId());
}, $csrf_key, '!acc/years/?msg=REOPEN');

$tpl->assign(compact('year', 'csrf_key'));

$tpl->display('acc/years/reopen.tpl');
