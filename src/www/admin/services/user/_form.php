<?php

namespace Garradin;

use Garradin\Accounting\Projects;
use Garradin\Services\Services;
use Garradin\Entities\Accounting\Year;
use KD2\DB\EntityManager;


if (!defined('\Garradin\ROOT')) {
	die();
}

assert(isset($tpl, $form_url, $create));

$current_only = f('past_services') ? 0 : 1;

// If there is only one user selected we can calculate the amount
$single_user_id = isset($users) && count($users) == 1 ? key($users) : null;
$copy_service ??= null;
$copy_service_only_paid ??= null;
$users ??= null;

$grouped_services = Services::listGroupedWithFees($single_user_id, $current_only);

if (!count($grouped_services)) {
	$current_only = false;
	$grouped_services = Services::listGroupedWithFees($single_user_id, $current_only);
}

if (!isset($count_all)) {
	$count_all = Services::count();
}

$has_past_services = count($grouped_services) != $count_all;

$today = new \DateTime;

$tpl->assign([
	'custom_js' => ['service_form.js'],
]);

$tpl->assign(compact('form_url', 'today', 'grouped_services', 'current_only', 'has_past_services',
	'create', 'copy_service', 'copy_service_only_paid', 'users'));

if ($session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ)) {
	$accounting_years = [];
	foreach (EntityManager::getInstance(Year::class)->iterate(sprintf('SELECT * FROM %s;', Year::TABLE)) as $accounting_year) {
		$accounting_years[$accounting_year->id] = $accounting_year;
	}

	$no_accounting_fee = true;
	foreach ($grouped_services as $service) {
		foreach ($service->fees as $fee)
			if ($no_accounting_fee && $accounting_years[$fee->id_year]->start_date <= $today && $today <= $accounting_years[$fee->id_year]->end_date) {
				$no_accounting_fee = false;
			}
	}
	$tpl->assign(compact('accounting_years', 'no_accounting_fee'));
}

$tpl->assign('projects', Projects::listAssocWithEmpty());
