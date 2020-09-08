<?php
namespace Garradin;

use Garradin\Accounting\Charts;
use Garradin\Accounting\Accounts;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$chart = $year->chart();
$accounts = $chart->accounts();

$tpl->assign('chart', $chart);
$tpl->assign('accounts_grouped', $accounts->listCommonGrouped());

$types = array_filter(Account::TYPES_NAMES, function ($v) { return $v !== ''; });
$tpl->assign('accounts_types', $types);

$tpl->display('acc/accounts/index.tpl');
