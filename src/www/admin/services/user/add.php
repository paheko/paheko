<?php
namespace Paheko;

use Paheko\Services\Services;
use Paheko\Users\Categories;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

// This controller allows to either select a user if none has been provided in the query string
// or subscribe a user to an activity (create a new Service_User entity)
// If $user_id is null then the form is just a select to choose a user

$count_all = Services::count();

if (!$count_all) {
	Utils::redirect(ADMIN_URL . 'services/?CREATE');
}

$services = Services::listAssocWithFees();
$categories = Categories::listAssoc();

$tpl->assign(compact('services', 'categories'));

$tpl->display('services/user/add.tpl');
