<?php

namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

if (qg('change_year')) {
	$session->set('acc_year', (int)qg('change_year'));
}

$current_year_id = $session->get('acc_year');

if (!$current_year_id) {
	$year = Years::getCurrentOpenYearIfSingle();

	if (!$year) {
		Utils::redirect(ADMIN_URL . '/acc/years/new.php?msg=FIRST');
	}

	$current_year_id = $year->id();
}

define('Garradin\SELECTED_YEAR_ID', $current_year_id);
$tpl->assign('current_year_id', SELECTED_YEAR_ID);
