<?php
namespace Garradin;

use Garradin\Accounting\Accounts;
use Garradin\Accounting\Charts;
use Garradin\Entities\Accounting\Chart;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

if (f('import') && $form->check('acc_charts_import', ['file' => 'file|required'])) {
	try {
		$chart = new Chart;
		$chart->importForm();
		$chart->save();
		$chart->accounts()->importUpload($_FILES['file']); // This will save everything
		Utils::redirect(ADMIN_URL . 'acc/charts/');
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}

$tpl->assign('columns', implode(', ', Accounts::EXPECTED_CSV_COLUMNS));
$tpl->assign('country_list', Utils::getCountryList());

$tpl->display('acc/charts/import.tpl');
