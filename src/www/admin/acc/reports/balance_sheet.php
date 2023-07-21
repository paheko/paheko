<?php

namespace Paheko;

use Paheko\Accounting\Reports;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Account;

require_once __DIR__ . '/_inc.php';

$balance = Reports::getBalanceSheet($criterias);
$tpl->assign('balance', $balance);

if (!empty($criterias['year'])) {
	$years = Years::listAssocExcept($criterias['year']);
	$tpl->assign('other_years', count($years) ? [null => '-- Ne pas comparer'] + $years : $years);
}

$tpl->display('acc/reports/balance_sheet.tpl');
