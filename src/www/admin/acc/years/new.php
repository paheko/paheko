<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Accounting\Charts;
use Garradin\Services\Fees;
use Garradin\Entities\Accounting\Year;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$form->runIf('new', function () {
	$year = new Year;
	$year->importForm();
	$year->save();

	if ($old_id = qg('from')) {
		$old = Years::get((int) $old_id);
		$changed = Fees::updateYear($old, $year);

		if (!$changed) {
			Utils::redirect(ADMIN_URL . 'acc/years/?msg=UPDATE_FEES');
		}
	}

	if (Years::countClosed()) {
		Utils::redirect(ADMIN_URL . 'acc/years/balance.php?id=' . $year->id());
	}
}, 'acc_years_new', '!acc/years/');

$new_dates = Years::getNewYearDates();
$tpl->assign('start_date', $new_dates[0]);
$tpl->assign('end_date', $new_dates[1]);
$tpl->assign('label', sprintf('Exercice %d', $new_dates[0]->format('Y')));
$tpl->assign('charts', Charts::listByCountry());

$tpl->display('acc/years/new.tpl');
