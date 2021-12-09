<?php

namespace Garradin;

use Garradin\Accounting\Reports;
use Garradin\Accounting\Years;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/_inc.php';

$tpl->assign('general', Reports::getStatement($criterias + ['exclude_type' => Account::TYPE_VOLUNTEERING]));
$tpl->assign('volunteering', Reports::getStatement($criterias + ['type' => Account::TYPE_VOLUNTEERING]));

if (!empty($criterias['year'])) {
	$tpl->assign('other_years', [null => '-- Ne pas comparer'] + Years::listClosedAssocExcept($criterias['year']));
}

$tpl->display('acc/reports/statement.tpl');
