<?php

namespace Garradin;

use Garradin\Accounting\Reports;

require_once __DIR__ . '/_inc.php';

$tpl->assign('journal', Reports::getJournal($criterias));

$tpl->display('acc/reports/journal.tpl');
