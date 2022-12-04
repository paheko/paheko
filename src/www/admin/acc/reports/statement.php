<?php

namespace Garradin;

use Garradin\Accounting\Reports;
use Garradin\Accounting\Years;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/_inc.php';

if ($f = qg('export')) {
	Reports::exportStatement($f, $criterias);
	return;
}

$general = Reports::getStatement($criterias + ['exclude_type' => [Account::TYPE_VOLUNTEERING_REVENUE, Account::TYPE_VOLUNTEERING_EXPENSE]]);
$tpl->assign('general', $general);
$tpl->assign('volunteering', Reports::getVolunteeringStatement($criterias, $general));

if (!empty($criterias['year'])) {
	$years = Years::listAssocExcept($criterias['year']);
	$tpl->assign('other_years', count($years) ? [null => '-- Ne pas comparer'] + $years : $years);
}

$tpl->display('acc/reports/statement.tpl');
