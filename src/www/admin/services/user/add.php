<?php
namespace Garradin;

use Garradin\Services\Services;
use Garradin\Entities\Services\Service_User;
use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$count_all = Services::count();

if (!$count_all) {
	Utils::redirect(ADMIN_URL . 'services/?CREATE');
}

$user_id = qg('user');

if (!$user_id && ($user_id = f('user'))) {
	$user_id = @key($user_id);
}

if (!$user_id) {
	$user_id = f('id_user');
}

$user_id = (int) $user_id ?: null;
$user_name = $user_id ? (new Membres)->getNom($user_id) : null;

if (!$user_name) {
	$user_id = null;
}

$form_url  = sprintf('?user=%d&', $user_id);
$csrf_key = 'service_save';

require __DIR__ . '/_form.php';

$form->runIf(f('save') || f('save_and_add_payment'), function () use ($session) {
	$su = Service_User::saveFromForm($session->getUser()->id);

	if (f('save_and_add_payment')) {
		$url = ADMIN_URL . 'services/user/payment.php?id=' . $su->id;
	}
	else {
		$url = ADMIN_URL . 'services/user/?id=' . $su->id_user;
	}

	Utils::redirect($url);
}, $csrf_key);

$selected_user = $user_name ? [$user_id => $user_name] : null;

$types_details = Transaction::getTypesDetails();
$account_targets = $types_details[Transaction::TYPE_REVENUE]->accounts[1]->targets_string;

$service_user = null;

$tpl->assign(compact('csrf_key', 'selected_user', 'account_targets', 'service_user'));

$tpl->display('services/user/add.tpl');
