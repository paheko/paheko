<?php

namespace Paheko;

use Paheko\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
	throw new UserException('Cette écriture n\'existe pas');
}

$transaction->assertCanBeModified();

$csrf_key = 'acc_delete_' . $transaction->id;

$form->runIf('delete', function () use ($transaction) {
	$transaction->delete();
	Utils::redirectParent('!acc/');
}, $csrf_key);

$tpl->assign(compact('transaction', 'csrf_key'));

$tpl->display('acc/transactions/delete.tpl');
