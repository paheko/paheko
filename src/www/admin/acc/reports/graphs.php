<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Accounting\Graph;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$year = Years::get((int)qg('year'));

$tpl->assign('graphs', Graph::URL_LIST);
$tpl->assign('year', $year);

$tpl->display('acc/reports/graphs.tpl');
