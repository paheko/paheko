<?php
namespace Paheko;

use Paheko\Accounting\Reports;
use Paheko\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

if (!CURRENT_YEAR_ID) {
	Utils::redirect('!acc/years/?msg=OPEN');
}

$pending_count = Transactions::listPendingCreditAndDebtForOtherYears(CURRENT_YEAR_ID)->count();

$tpl->assign(compact('pending_count'));

$tpl->assign('chart_id', $current_year->id_chart);
$tpl->assign('grouped_accounts', Reports::getClosingSumsFavoriteAccounts(['year' => CURRENT_YEAR_ID]));

$tpl->display('acc/accounts/index.tpl');
