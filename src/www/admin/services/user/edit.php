<?php
namespace Paheko;

use Paheko\Services\Subscriptions;
use Paheko\Users\Users;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$subscription = Subscriptions::get((int) qg('id'));

if (!$subscription) {
	throw new UserException("Cette inscription n'existe pas");
}

$csrf_key = 'subscription_edit_' . $subscription->id();
$users = [$subscription->id_user => Users::getName($subscription->id_user)];
$form_url = sprintf('edit.php?id=%d&', $subscription->id());
$create = false;

require __DIR__ . '/_form.php';

$form->runIf('save', function () use ($subscription) {
	$subscription->importForm();
	$subscription->importForm(['paid' => (bool)f('paid')]);
	$subscription->updateExpectedAmount();
	$subscription->save();
}, $csrf_key, ADMIN_URL . 'services/user/?id=' . $subscription->id_user);

$tpl->assign(compact('csrf_key', 'subscription'));

$tpl->display('services/user/edit.tpl');
