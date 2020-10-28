<?php

namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Accounting\Charts;

require_once __DIR__ . '/../../_inc.php';

header('X-Frame-Options: SAMEORIGIN', true);

$charts = $chart = null;
$targets = qg('targets');

if (qg('chart')) {
	$chart = Charts::get((int)qg('chart'));
}
elseif ($current_year) {
	$chart = $current_year->chart();
}

if (qg('chart_choice')) {
	$charts = Charts::listAssoc();
}

if (!$chart) {
	throw new UserException('Aucun plan comptable ouvert actuellement');
}

$accounts = $chart->accounts();

$tpl->assign(compact('chart', 'charts', 'targets'));

if (!$targets) {
	$tpl->assign('accounts', $accounts->listAll());
}
else {
	$tpl->assign('grouped_accounts', $accounts->listCommonGrouped(explode(':', $targets)));
}


$tpl->display('acc/charts/accounts/selector.tpl');