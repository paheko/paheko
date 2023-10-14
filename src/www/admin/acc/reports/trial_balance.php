<?php

namespace Paheko;

use Paheko\Accounting\Reports;

require_once __DIR__ . '/_inc.php';

$simple = !empty($session->user()->preferences->accounting_expert) ? false : true;
$balance = Reports::getTrialBalance($criterias, (bool) $simple);

$tpl->assign(compact('simple', 'balance'));

$tpl->display('acc/reports/trial_balance.tpl');
