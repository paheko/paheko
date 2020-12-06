<?php

namespace Garradin;

use Garradin\Accounting\Reports;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/_inc.php';

$balance = Reports::getBalanceSheet($criterias);

$liability = $balance[Account::LIABILITY];
$asset = $balance[Account::ASSET];
$liability_sum = $balance['sums'][Account::LIABILITY];
$asset_sum = $balance['sums'][Account::ASSET];

$tpl->assign(compact('liability', 'asset', 'liability_sum', 'asset_sum'));

$tpl->display('acc/reports/balance_sheet.tpl');
