<?php

namespace Paheko;

use Paheko\Accounting\Reports;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$expert = Session::getLoggedUser()->preferences->accounting_expert ?? false;
$tpl->assign('ledger', Reports::getGeneralLedger($criterias, !$expert));

$tpl->display('acc/reports/ledger.tpl');
