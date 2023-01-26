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

// Quick fix. Will be better implemented inside 1.3 version
foreach ($grouped_services as &$service) {
	foreach ($service->fees as $key => $fee) {
		if ($fee->id_year != $session->get('acc_year')) {
			unset($service->fees[$key]);
		}
	}
}

$tpl->assign('accounting_year', EntityManager::findOneById(Year::class, $session->get('acc_year')));

$has_past_services = count($grouped_services) != $count_all;

$today = new \DateTime;

$tpl->assign([
	'custom_js' => ['service_form.js'],
]);

$tpl->assign(compact('form_url', 'today', 'grouped_services', 'current_only', 'has_past_services',
	'create', 'copy_service', 'copy_service_only_paid', 'users'));

$tpl->assign('projects', Projects::listAssocWithEmpty());
