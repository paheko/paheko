<?php

namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

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

if (!count($criterias))
{
	throw new UserException('Critère de rapport inconnu.');
}

$tpl->assign('criterias_query', http_build_query($criterias));