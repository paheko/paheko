<?php
namespace Garradin;

use Garradin\Accounting\Plans;
use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$plan = (new Plans)->get(qg('id'));
$accounts = new Accounts;

$tpl->assign('plan', $plan);
$tpl->assign('accounts', $accounts->listForPlan($plan->id()));

$tpl->display('admin/acc/plans/accounts/index.tpl');
