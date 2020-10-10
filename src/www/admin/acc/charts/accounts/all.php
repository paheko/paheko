<?php
namespace Garradin;

use Garradin\Accounting\Charts;

require_once __DIR__ . '/../../_inc.php';

$chart = null;

if ($id = (int)qg('id')) {
	$chart = Charts::get($id);
}
elseif (CURRENT_YEAR_ID) {
	$year = $current_year;
	$chart = $year->chart();
}

if (!$chart) {
	throw new UserException('Aucun plan comptable spécifié');
}

$accounts = $chart->accounts();

$tpl->assign('chart', $chart);
$tpl->assign('accounts', $accounts->listAll());
$tpl->display('acc/charts/accounts/all.tpl');
