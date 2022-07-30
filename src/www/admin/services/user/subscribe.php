<?php
namespace Garradin;

use Garradin\Services\Services;
use Garradin\Users\Users;
use Garradin\Entities\Services\Service_User;
use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

// This controller allows to either select a user if none has been provided in the query string
// or subscribe a user to an activity (create a new Service_User entity)
// If $user_id is null then the form is just a select to choose a user

$count_all = Services::count();

if (!$count_all) {
	Utils::redirect(ADMIN_URL . 'services/?CREATE');
}

$users = null;
$copy_service = null;
$copy_service_only_paid = null;

if (qg('user') && ($name = Users::getName((int)qg('user')))) {
	$users = [(int)qg('user') => $name];
}
elseif (f('users') && is_array(f('users')) && count(f('users'))) {
	$users = f('users');
	$users = array_filter($users, 'intval', \ARRAY_FILTER_USE_KEY);
}
elseif (f('copy_service')
	&& ($copy_service = Services::get((int)f('copy_service')))) {
	$copy_service_only_paid = (bool) f('copy_service_only_paid');
}
else {
	throw new UserException('Aucun membre n\'a été sélectionné');
}

$form_url = '?';
$csrf_key = 'service_save';
$create = true;

// Only load the form if a user has been selected
require __DIR__ . '/_form.php';

$form->runIf(f('save') || f('save_and_add_payment'), function () use ($session, $users, $copy_service, $copy_service_only_paid) {
	if ($copy_service) {
		$users = $copy_service->getUsers($copy_service_only_paid);
	}

	$su = Service_User::createFromForm($users, $session->getUser()->id, $copy_service ? true : false);

	if (count($users) > 1) {
		$url = ADMIN_URL . 'services/details.php?id=' . $su->id_service;
	}
	elseif (f('save_and_add_payment')) {
		$url = ADMIN_URL . 'services/user/payment.php?id=' . $su->id;
	}
	else {
		$url = ADMIN_URL . 'services/user/?id=' . $su->id_user;
	}

	Utils::redirect($url);
}, $csrf_key);

$t = new Transaction;
$t->type = $t::TYPE_REVENUE;
$types_details = $t->getTypesDetails();
$account_targets = $types_details[Transaction::TYPE_REVENUE]->accounts[1]->targets_string;

$service_user = null;

$tpl->assign(compact('csrf_key', 'users', 'account_targets', 'service_user'));

$tpl->display('services/user/subscribe.tpl');
