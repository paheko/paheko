<?php
namespace Garradin;

use Garradin\Accounting\Charts;
use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$chart = (new Charts)->get(qg('id'));
$accounts = new Accounts;

$tpl->assign('chart', $chart);
$tpl->assign('accounts', $accounts->listForChart($chart->id()));

$tpl->display('admin/acc/accounts/index.tpl');
