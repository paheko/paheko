<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if (!$year->isLocked()) {
	throw new UserException('Cet exercice n\'est pas verrouillé.');
}

$csrf_key = 'unlock_' . $year->id();

$form->runIf('unlock', function () use ($year) {
	$year->set('status', $year::OPEN);
	$year->save();
}, $csrf_key, '!acc/years/');

$tpl->assign(compact('year', 'csrf_key'));

$tpl->display('acc/years/unlock.tpl');
