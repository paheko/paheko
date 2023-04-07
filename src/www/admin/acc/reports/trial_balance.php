<?php

namespace Garradin;

use Garradin\Accounting\Reports;

require_once __DIR__ . '/_inc.php';

$simple = qg('simple') === null || qg('simple') ? 'simple' : null;
$balance = Reports::getTrialBalance($criterias, (bool) $simple);

$tpl->assign(compact('simple', 'balance'));

if ($f = qg('export')) {
	$table = $tpl->fetch('acc/reports/trial_balance.tpl');
	CSV::exportHTML($f, $table, 'Balance générale');
	return;
}

$tpl->display('acc/reports/trial_balance.tpl');
