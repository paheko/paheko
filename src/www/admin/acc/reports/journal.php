<?php

namespace Paheko;

use Paheko\Accounting\Projects;
use Paheko\Accounting\Reports;

require_once __DIR__ . '/_inc.php';

$journal = Reports::getJournal($criterias);

$tpl->assign('has_projects', Projects::count() > 0);
$tpl->assign(compact('journal'));

$tpl->display('acc/reports/journal.tpl');
