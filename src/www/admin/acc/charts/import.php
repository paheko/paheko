<?php
namespace Garradin;

use Garradin\Accounting\Accounts;
use Garradin\Accounting\Charts;
use Garradin\Entities\Accounting\Chart;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$form->runIf('import', function () {
	$db = DB::getInstance();
	$db->begin();

	$chart = new Chart;
	$chart->importForm();
	$chart->save();
	$chart->accounts()->importUpload($_FILES['file']); // This will save everything

	$db->commit();
}, 'acc_charts_import', '!acc/charts/');

$tpl->assign('columns', implode(', ', Accounts::EXPECTED_CSV_COLUMNS));
$tpl->assign('country_list', Utils::getCountryList());

$tpl->display('acc/charts/import.tpl');
