<?php
namespace Garradin;

use Garradin\Accounting\Reports;
use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

if (!CURRENT_YEAR_ID) {
	Utils::redirect('!acc/years/?msg=OPEN');
}

$pending_count = Transactions::listPendingCreditAndDebtForClosedYears()->count();

$tpl->assign(compact('pending_count'));

$tpl->assign('chart_id', $current_year->id_chart);
$tpl->assign('grouped_accounts', Reports::getClosingSumsFavoriteAccounts(['year' => CURRENT_YEAR_ID]));

$tpl->display('acc/accounts/index.tpl');
