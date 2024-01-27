<?php
namespace Paheko;

use Paheko\Services\Subscriptions;
use Paheko\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);
$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$subscription = Subscriptions::get((int)qg('id'));

if (!$subscription) {
	throw new UserException("Cette inscription n'existe pas");
}

$csrf_key = 'service_link';

$form->runIf('save', function () use ($subscription) {
	$id = (int)f('id_transaction');
	$transaction = Transactions::get($id);

	if (!$transaction) {
		throw new UserException('Impossible de trouver l\'écriture #' . $id);
	}

	$transaction->linkToSubscription($subscription->id);
}, $csrf_key, '!acc/transactions/subscription.php?id=' . $subscription->id . '&user=' . $subscription->id_user);

$tpl->assign(compact('csrf_key'));

$tpl->display('services/subscription/link.tpl');
