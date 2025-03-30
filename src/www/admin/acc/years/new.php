<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Accounting\Charts;
use Paheko\Services\Fees;
use Paheko\Entities\Accounting\Year;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year = new Year;

$form->runIf('new', function () use ($year) {
	$year->importForm();
	$year->save();
}, 'acc_years_new', '!acc/years/');

$new_dates = Years::getNewYearDates();
$year->start_date = $new_dates[0];
$year->end_date = $new_dates[1];
$year->label = sprintf('Exercice %s', $year->label_years());

$tpl->assign(compact('year'));

$tpl->assign('charts', Charts::listByCountry(true));

$tpl->display('acc/years/new.tpl');
