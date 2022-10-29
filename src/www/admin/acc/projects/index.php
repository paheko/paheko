<?php
namespace Garradin;

use Garradin\Accounting\Projects;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$by_year = (bool)qg('by_year');
$order_code = (bool)qg('order_code');

$tpl->assign(compact('by_year', 'order_code'));
$tpl->assign('list', Projects::getBalances($by_year, $order_code));

$tpl->assign('projects_count', Projects::count());

$tpl->display('acc/projects/index.tpl');
