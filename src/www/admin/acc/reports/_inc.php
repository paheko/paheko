<?php

namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$criterias = [];

if (qg('analytical'))
{
	$account = Accounts::get((int) qg('analytical'));

	if (!$account) {
		throw new UserException('Numéro de compte analytique inconnu.');
	}

	$criterias['analytical'] = $account->id();
	$tpl->assign('analytical', $account);
}

if (qg('year'))
{
	$year = Years::get((int) qg('year'));

	if (!$year) {
		throw new UserException('Exercice inconnu.');
	}

	$criterias['year'] = $year->id();
	$tpl->assign('year', $year);
	$tpl->assign('close_date', $year->closed ? $year->end_date : time());
}

if (qg('analytical_only')) {
	$criterias['analytical_only'] = true;
}

if (!count($criterias))
{
	throw new UserException('Critère de rapport inconnu.');
}

if ($y2 = Years::get((int)qg('compare_year'))) {
	$tpl->assign('year2', $y2);
	$criterias['compare_year'] = $y2->id;
}

$tpl->assign('criterias', $criterias);
$tpl->assign('criterias_query', http_build_query($criterias));