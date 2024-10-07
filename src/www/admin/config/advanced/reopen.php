<?php
namespace Paheko;

use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$form->runIf('reopen_ok', function () use ($session) {
	$year = Years::get((int) f('year'));

	if (!$year) {
		throw new UserException('L\'exercice sélectionné est introuvable.');
	}

	if (!$year->isClosed()) {
		throw new UserException('Cet exercice n\'est pas clôturé.');
	}

	$year->reopen($session->getUser()->id);
}, 'reopen_year', '!config/advanced/?msg=REOPEN');

var_dump(Years::listClosedAssoc()); exit;
$tpl->assign('closed_years', Years::listClosedAssoc());

$tpl->display('config/advanced/reopen.tpl');
