<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Accounting\Graph;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$year = Years::get((int)qg('year'));

$tpl->assign('graphs', Graph::URL_LIST);
$tpl->assign('year', $year);

$tpl->display('acc/reports/graphs.tpl');
