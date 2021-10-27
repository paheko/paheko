<?php

namespace Garradin;

use Garradin\Services\Services;


if (!defined('\Garradin\ROOT')) {
	die();
}

assert(isset($tpl, $form_url));

$current_only = !qg('past_services');

$grouped_services = Services::listGroupedWithFees($user_id, $current_only);

if (!count($grouped_services)) {
	Utils::redirect($form_url . 'past_services=' . (int) $current_only);
}

if (!isset($count_all)) {
	$count_all = Services::count();
}

$has_past_services = count($grouped_services) != $count_all;

$today = new \DateTime;

$tpl->assign([
	'custom_js' => ['service_form.js'],
]);

$tpl->assign(compact('form_url', 'today', 'grouped_services', 'user_name', 'user_id', 'current_only', 'has_past_services'));
