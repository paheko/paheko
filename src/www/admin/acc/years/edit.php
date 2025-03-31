<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Year;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

$year->assertCanBeModified();

$csrf_key = 'acc_years_edit_' . $year->id();

$form->runIf('edit', function () use ($year) {
	$year->importForm();
	$year->save();
}, $csrf_key, ADMIN_URL . 'acc/years/');

$tpl->assign(compact('year', 'csrf_key'));

$tpl->display('acc/years/edit.tpl');
