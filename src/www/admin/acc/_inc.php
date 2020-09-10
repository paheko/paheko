<?php

namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

if ($year_id = $session->get('acc_year')) {
	$year = Years::get($year_id);
}
else {
	$year = Years::getCurrentOpenYearIfSingle();

	if ($year) {
		$year_id = $year->id();
		$session->set('acc_year', $year_id);
	}
	else {
		Utils::redirect(ADMIN_URL . '/acc/years/?msg=SELECT_YEAR');
	}
}

$chart = $year->chart();

$tpl->assign('year', $year);
$tpl->assign('chart', $chart);
