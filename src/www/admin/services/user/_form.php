<?php

namespace Paheko;

use Paheko\Accounting\Projects;
use Paheko\Services\Services;


if (!defined('\Paheko\ROOT')) {
	die();
}

assert(isset($tpl, $form_url, $create));

// If there is only one user selected we can calculate the amount
$single_user_id = isset($users) && count($users) == 1 ? key($users) : null;
$copy_service ??= null;
$copy_service_only_paid ??= null;
$users ??= null;

$grouped_services = Services::listGroupedWithFees($single_user_id);

$today = new \DateTime;

$tpl->assign([
	'custom_js' => ['service_form.js'],
]);

$tpl->assign(compact('form_url', 'today', 'grouped_services',
	'create', 'copy_service', 'copy_service_only_paid'));

$tpl->assign_by_ref('users', $users);

$tpl->assign('projects', Projects::listAssoc());
