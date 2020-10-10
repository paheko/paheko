<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Accounting\Charts;
use Garradin\Entities\Accounting\Year;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

if (f('new') && $form->check('acc_years_new')) {
	try {
		$year = new Year;
		$year->importForm();
		$year->save();

		Utils::redirect(ADMIN_URL . 'acc/years/balance.php?id=' . $year->id());
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$new_dates = Years::getNewYearDates();
$tpl->assign('start_date', $new_dates[0]);
$tpl->assign('end_date', $new_dates[1]);
$tpl->assign('charts', Charts::listByCountry());

$tpl->display('acc/years/new.tpl');
