<?php

namespace Paheko;

use Paheko\Accounting\Reports;

require_once __DIR__ . '/_inc.php';

$journal = Reports::getJournal($criterias);

$tpl->assign(compact('journal'));

$tpl->display('acc/reports/journal.tpl');
