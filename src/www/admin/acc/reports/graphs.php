<?php
namespace Paheko;

use Paheko\Accounting\Graph;
use Paheko\Accounting\Reports;
use Paheko\Accounting\Years;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$year = Years::get((int)qg('year'));

$tpl->assign('graphs', Graph::URL_LIST);
$tpl->assign('year', $year);

$tpl->assign('nb_transactions', Reports::countTransactions($criterias));

$tpl->display('acc/reports/graphs.tpl');
