<?php
namespace Paheko;

use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$form->runIf('reopen_ok', function () use ($session) {
	$year = Years::get((int) f('year'));
	$year->reopen($session->getUser()->id);
}, 'reopen_year', '!config/advanced/?msg=REOPEN');

$tpl->assign('closed_years', Years::listClosedAssoc());

$tpl->display('config/advanced/reopen.tpl');
