<?php
namespace Garradin;

use Garradin\Accounting\Reports;

require_once __DIR__ . '/../_inc.php';

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$tpl->assign('chart_id', $current_year->id_chart);
$tpl->assign('grouped_accounts', Reports::getClosingSumsFavoriteAccounts(['year' => CURRENT_YEAR_ID]));

$tpl->display('acc/accounts/index.tpl');
