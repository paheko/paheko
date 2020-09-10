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

$types = $accounts->getTypesParents();
$types = array_map(function ($v, $k) { return sprintf('%s (%s)', Account::TYPES_NAMES[$k], $v); }, $types, array_keys($types));
$tpl->assign('accounts_types', $types);

$tpl->display('acc/accounts/index.tpl');
