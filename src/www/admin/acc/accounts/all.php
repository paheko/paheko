<?php
namespace Paheko;

use Paheko\Accounting\Reports;

require_once __DIR__ . '/../_inc.php';

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$criterias = ['year' => CURRENT_YEAR_ID];
$tpl->assign('balance', Reports::getTrialBalance($criterias, true));

$tpl->display('acc/accounts/all.tpl');
