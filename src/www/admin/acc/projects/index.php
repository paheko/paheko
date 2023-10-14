<?php
namespace Paheko;

use Paheko\Accounting\Projects;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$by_year = (bool)qg('by_year');

$list = Projects::getBalances($by_year);

$tpl->assign(compact('by_year', 'list'));
$tpl->assign('export', false);

$tpl->assign('projects_count', Projects::count());

$tpl->display('acc/projects/index.tpl');
