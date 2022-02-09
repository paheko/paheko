<?php
namespace Garradin;

use Garradin\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$form->runIf('install', function () {
	Charts::install(f('code'));
}, 'acc_charts_install', '!acc/charts/');

$tpl->assign('list', Charts::listInstallable());

$tpl->display('acc/charts/install.tpl');
