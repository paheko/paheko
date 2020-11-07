<?php

namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Accounting\Charts;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

header('X-Frame-Options: SAMEORIGIN', true);

$targets = qg('targets');

$chart = null;

if (qg('chart')) {
	$chart = Charts::get((int)qg('chart'));
}
elseif (qg('year')) {
	$year = Years::get((int)qg('year'));

	if ($year) {
		$chart = $year->chart();
	}
}
elseif ($current_year) {
	$chart = $current_year->chart();
}

if (!$chart) {
	throw new UserException('Aucun exercice ouvert disponible');
}

$accounts = $chart->accounts();

$tpl->assign(compact('chart', 'targets'));

if (!$targets) {
	$tpl->assign('accounts', $accounts->listAll());
}
else {
	$tpl->assign('grouped_accounts', $accounts->listCommonGrouped(explode(':', $targets)));
}


$tpl->display('acc/charts/accounts/selector.tpl');