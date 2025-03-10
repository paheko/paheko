<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Services\Fees;

require_once __DIR__ . '/../_inc.php';

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

$fees = Fees::listByYearId($year->id());

$tpl->assign(compact('year', 'fees'));

$tpl->display('acc/years/links.tpl');
