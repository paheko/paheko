<?php

namespace Garradin;

use Garradin\Accounting\Reports;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/_inc.php';

$tpl->assign('general', Reports::getStatement($criterias + ['exclude_type' => Account::TYPE_VOLUNTEERING]));
$tpl->assign('volunteering', Reports::getStatement($criterias + ['type' => Account::TYPE_VOLUNTEERING]));

$tpl->display('acc/reports/statement.tpl');
