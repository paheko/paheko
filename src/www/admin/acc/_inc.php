<?php

namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$current_year_id = $session->get('acc_year');

if ($current_year_id) {
	// Check that the year is still valid
	$current_year = Years::get($current_year_id);

	if ($current_year->closed) {
		$current_year_id = null;
		$session->set('acc_year', null);
	}
}

if (!$current_year_id) {
	$current_year = Years::getCurrentOpenYearIfSingle();

	if (!$current_year) {
		Utils::redirect(ADMIN_URL . '/acc/years/new.php?msg=FIRST');
	}

	$current_year_id = $current_year->id();
}

if ($session->get('acc_year') != $current_year_id) {
	$session->set('acc_year', $current_year_id);
}

define('Garradin\CURRENT_YEAR_ID', $current_year->id());

$tpl->assign('current_year', $current_year);
