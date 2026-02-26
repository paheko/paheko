<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Accounting\Charts;
use Paheko\Services\Fees;
use Paheko\Entities\Accounting\Year;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

// Install new accounting charts
// FIXME: remove in 2028+
Charts::migrateTo2025();

$year = new Year;

$form->runIf('new', function () use ($year) {
	$year->importForm();
	$year->save();
}, 'acc_years_new', '!acc/years/');

$new_dates = Years::getNewYearDates();
$year->start_date = $new_dates[0];
$year->end_date = $new_dates[1];
$year->label = sprintf('Exercice %s', $year->label_years());

$chart_selector_default = null;

if (Charts::hasActiveCustomCharts()) {
	$chart_selector_default = 'Sélectionner un plan comptable';
}
elseif ($id = Charts::getDefaultChartId(Config::getInstance()->country)) {
	$year->id_chart = $id;
}

$tpl->assign(compact('year', 'chart_selector_default'));

$tpl->assign('charts', Charts::listByCountry(true));

$tpl->display('acc/years/new.tpl');
