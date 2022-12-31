<?php

namespace Garradin;

use Garradin\Accounting\Reports;

require_once __DIR__ . '/_inc.php';

$simple = !empty($session->user()->preferences->accounting_expert) ? false : true;

$tpl->assign(compact('simple'));

$tpl->assign('balance', Reports::getTrialBalance($criterias, (bool) $simple));

$tpl->display('acc/reports/trial_balance.tpl');
