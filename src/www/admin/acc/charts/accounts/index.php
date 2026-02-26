<?php
namespace Paheko;

use Paheko\Accounting\Charts;

$types = null; // Just to silence phpstan

require_once __DIR__ . '/_inc.php';

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

if (!$chart->country) {
	Utils::redirect('!acc/charts/accounts/all.php?id=' . $chart->id);
}

$accounts = $chart->accounts();

$tpl->assign(compact('chart'));
$tpl->assign('accounts_grouped', $accounts->listCommonGrouped(compact('types'), false));
$tpl->display('acc/charts/accounts/index.tpl');
