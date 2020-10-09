<?php

namespace Garradin;

use Garradin\Accounting\Reports;

require_once __DIR__ . '/_inc.php';

$tpl->assign('ledger', Reports::getGeneralLedger($criterias));

$tpl->display('acc/reports/ledger.tpl');
