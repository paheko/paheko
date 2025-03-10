<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if (!$year->isOpen()) {
	throw new UserException('Cet exercice n\'est pas ouvert.');
}

$csrf_key = 'lock_' . $year->id();

$form->runIf('lock', function () use ($year) {
	$year->set('status', $year::LOCKED);
	$year->save();
}, $csrf_key, '!acc/years/');

$tpl->assign(compact('year', 'csrf_key'));

$tpl->display('acc/years/lock.tpl');
