<?php
namespace Paheko;

use Paheko\Entities\Accounting\Chart;
use Paheko\Accounting\Accounts;
use Paheko\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$tpl->assign('list', Charts::list());

if ($session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)) {
	$csrf_key = 'acc_charts_new';

	$form->runIf(f('type') == 'copy', function () {
		Charts::copyFrom((int) f('copy'), f('label'), f('country'));
	}, $csrf_key, '!acc/charts/');

	$form->runIf(f('type') == 'install', function () {
		Charts::install(f('install'));
	}, $csrf_key, '!acc/charts/');

	$form->runIf(f('type') == 'import', function () {
		Charts::import('file', f('label'), f('import_country'));
	}, $csrf_key, '!acc/charts/');

	$tpl->assign(compact('csrf_key'));

	$tpl->assign('columns', implode(', ', Chart::COLUMNS));
	$tpl->assign('country_list', Utils::getCountryList());

	$tpl->assign('from', (int)qg('from'));
	$tpl->assign('charts_grouped', Charts::listByCountry());
	$tpl->assign('country_list', Chart::COUNTRY_LIST + ['' => '— Autre']);

	$tpl->assign('install_list', Charts::listInstallable());
}

$tpl->display('acc/charts/index.tpl');
