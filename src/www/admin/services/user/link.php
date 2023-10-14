<?php
namespace Paheko;

use Paheko\Services\Services_User;
use Paheko\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_WRITE);
$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$su = Services_User::get((int)qg('id'));

if (!$su) {
	throw new UserException("Cette inscription n'existe pas");
}

$csrf_key = 'service_link';

$form->runIf('save', function () use ($su) {
	$id = (int)f('id_transaction');
	$transaction = Transactions::get($id);

	if (!$transaction) {
		throw new UserException('Impossible de trouver l\'écriture #' . $id);
	}

	$transaction->linkToUser($su->id_user, $su->id);
}, $csrf_key, '!acc/transactions/service_user.php?id=' . $su->id . '&user=' . $su->id_user);

$tpl->assign(compact('csrf_key'));

$tpl->display('services/user/link.tpl');
