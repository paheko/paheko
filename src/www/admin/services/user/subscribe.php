<?php
namespace Paheko;

use Paheko\Services\Fees;
use Paheko\Services\Services;
use Paheko\Users\Categories;
use Paheko\Users\Users;
use Paheko\Accounting\Projects;
use Paheko\Entities\Services\Service_User;
use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Transaction;

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
$copy_fee = null;
$copy_only_paid = null;
$allow_users_edit = true;
$copy = substr((string) f('copy'), 0, 1);
$copy_id = (int) substr((string) f('copy'), 1);

if (qg('user') && ($name = Users::getName((int)qg('user')))) {
	$users = [(int)qg('user') => $name];
	$allow_users_edit = false;
}
elseif (f('users') && is_array(f('users')) && count(f('users'))) {
	$users = f('users');
	$users = array_filter($users, 'intval', \ARRAY_FILTER_USE_KEY);
}
elseif (($copy == 's' && ($copy_service = Services::get($copy_id)))
	|| ($copy == 'f' && ($copy_fee = Fees::get($copy_id)))) {
	$copy_only_paid = (bool) f('copy_only_paid');
}
elseif (f('category')) {
	$category = Categories::get((int)f('category'));

	if (!$category) {
		throw new UserException('Catégorie inconnue.');
	}

	$users = iterator_to_array(Users::iterateAssocByCategory($category->id));
}
elseif (qg('users')) {
	$users = explode(',', qg('users'));
	$users = array_map('intval', $users);
	$users = Users::getNames($users);
}
else {
	throw new UserException('Aucun membre n\'a été sélectionné');
}

if (null !== $users) {
	natcasesort($users);
}

$form_url = '?';
$csrf_key = 'service_save';
$create = true;

// Only load the form if a user has been selected
require __DIR__ . '/_form.php';

$form->runIf('save', function () use ($session, &$users, $copy_service, $copy_fee, $copy_only_paid) {
	if ($copy_service) {
		$users = $copy_service->getUsers($copy_only_paid);
	}
	elseif ($copy_fee) {
		$users = $copy_fee->getUsers($copy_only_paid);
	}

	$su = Service_User::createFromForm($users, $session::getUserId(), $copy_service ? true : false);

	Utils::reloadParentFrameIfDialog();

	if (count($users) > 1) {
		$url = ADMIN_URL . 'services/details.php?id=' . $su->id_service;
	}
	else {
		$url = ADMIN_URL . 'services/user/?id=' . $su->id_user;
	}

	Utils::redirect($url);
}, $csrf_key);

if (null !== $users && !count($users)) {
	throw new ValidationException('Aucun membre sélectionné ne peut être inscrit, car ils sont tous déjà inscrits à cette activité et à la date indiquée.');
}

$t = new Transaction;
$t->type = $t::TYPE_REVENUE;
$types_details = $t->getTypesDetails();
$account_types = $types_details[Transaction::TYPE_REVENUE]->accounts[1]->types_string;

$service_user = null;

$tpl->assign(compact('csrf_key', 'users', 'account_types', 'service_user', 'allow_users_edit', 'copy_service', 'copy_fee', 'copy_only_paid'));
$tpl->assign('projects', Projects::listAssoc());

$tpl->display('services/user/subscribe.tpl');
