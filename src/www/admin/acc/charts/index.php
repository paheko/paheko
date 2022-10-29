<?php
namespace Garradin;

use Garradin\Entities\Accounting\Chart;
use Garradin\Accounting\Accounts;
use Garradin\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$tpl->assign('list', Charts::list());

if ($session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)) {
	if (f('new') && $form->check('acc_charts_new')) {
		try {
			$chart = new Chart;
			$chart->importForm();
			$chart->save();

			if (f('copy')) {
				$chart->accounts()->copyFrom((int) f('copy'));
			}

			Utils::redirect(Utils::getSelfURI(false));
		}
		catch (UserException $e) {
			$form->addError($e->getMessage());
		}
	}

	$form->runIf('install', function () {
		Charts::install(f('install'));
	}, 'acc_charts_new', '!acc/charts/');


	$form->runIf('import', function () {
		$db = DB::getInstance();
		$db->begin();

		$chart = new Chart;
		$chart->importForm();
		$chart->save();
		$chart->accounts()->importUpload($_FILES['file']); // This will save everything

		$db->commit();
	}, 'acc_charts_new', '!acc/charts/');

	$tpl->assign('columns', implode(', ', Accounts::EXPECTED_CSV_COLUMNS));
	$tpl->assign('country_list', Utils::getCountryList());

	$tpl->assign('from', (int)qg('from'));
	$tpl->assign('charts_groupped', Charts::listByCountry());
	$tpl->assign('country_list', Utils::getCountryList());

	$tpl->assign('install_list', Charts::listInstallable());
}

$tpl->display('acc/charts/index.tpl');
