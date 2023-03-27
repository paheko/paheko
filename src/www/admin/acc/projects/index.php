<?php
namespace Garradin;

use Garradin\Accounting\Projects;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$by_year = (bool)qg('by_year');

$list = Projects::getBalances($by_year);

$tpl->assign(compact('by_year', 'list'));
$tpl->assign('export', false);

if ($f = qg('export')) {
	$tpl->assign('export', true);
	$tpl->assign('caption', 'Projets');
	$table = $tpl->fetch('acc/projects/_list.tpl');

	CSV::exportHTML($f, $table, 'Projets');
	return;
}


$tpl->assign('projects_count', Projects::count());

$tpl->display('acc/projects/index.tpl');
