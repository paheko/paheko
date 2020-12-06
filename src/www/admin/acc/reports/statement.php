<?php

namespace Garradin;

use Garradin\Accounting\Reports;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/_inc.php';

$revenue = Reports::getClosingSumsWithAccounts($criterias + ['position' => Account::REVENUE]);
$expense = Reports::getClosingSumsWithAccounts($criterias + ['position' => Account::EXPENSE], null, true);

$get_sum = function (array $in): int {
	$sum = 0;

	foreach ($in as $row) {
		$sum += $row->sum;
	}

	return abs($sum);
};

$revenue_sum = $get_sum($revenue);
$expense_sum = $get_sum($expense);
$result = $revenue_sum - $expense_sum;

$tpl->assign(compact('revenue', 'expense', 'revenue_sum', 'expense_sum', 'result'));

$tpl->display('acc/reports/statement.tpl');
