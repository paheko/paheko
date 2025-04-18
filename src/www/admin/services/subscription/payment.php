<?php
namespace Paheko;

use Paheko\Services\Subscriptions;
use Paheko\Accounting\Accounts;
use Paheko\Accounting\Projects;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Users\Users;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$su = Subscriptions::get((int)qg('id'));

if (!$su) {
	throw new UserException("Cette inscription n'existe pas");
}

$fee = $su->fee();

if (!$fee || !$fee->id_year) {
	throw new UserException('Cette inscription n\'est pas liée à un tarif relié à la comptabilité, il n\'est pas possible de saisir un règlement.');
}

$user_name = Users::getName($su->id_user);

$csrf_key = 'service_pay';

$form->runIf(f('save') || f('save_and_add_payment'), function () use ($su, $session) {
	$su->addPayment($session);

	if ($su->paid != (bool) f('paid')) {
		$su->paid = (bool) f('paid');
		$su->save();
	}
}, $csrf_key, '!users/subscriptions.php?id=' . $su->id_user);

$t = new Transaction;
$t->type = $t::TYPE_REVENUE;
$types_details = $t->getTypesDetails();

$account_types = $types_details[Transaction::TYPE_REVENUE]->accounts[1]->types_string;

$tpl->assign('projects', Projects::listAssoc());

$tpl->assign(compact('csrf_key', 'account_types', 'user_name', 'su', 'fee'));

$tpl->display('services/subscription/payment.tpl');
