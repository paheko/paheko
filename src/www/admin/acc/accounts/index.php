<?php
namespace Garradin;

use Garradin\Accounting\Accounts;
use Garradin\Accounting\Charts;
use Garradin\Accounting\Years;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/../_inc.php';

$year = Years::get(SELECTED_YEAR_ID);

$chart = $year->chart();
$accounts = $chart->accounts();

$tpl->assign('chart', $chart);
$tpl->assign('accounts_grouped', $accounts->listCommonGrouped());

$tpl->display('acc/accounts/index.tpl');
