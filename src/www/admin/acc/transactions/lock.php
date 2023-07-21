<?php

namespace Paheko;

use Paheko\Entities\Accounting\Transaction;
use Paheko\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
	throw new UserException('Cette écriture n\'existe pas');
}

$transaction->assertCanBeModified();

$csrf_key = 'acc_transaction_lock_' . $transaction->id();

$form->runIf('lock', function() use ($transaction) {
	$transaction->lock();
}, $csrf_key, '!acc/transactions/details.php?id=' . $transaction->id());

$tpl->assign(compact('csrf_key', 'transaction'));

$tpl->display('acc/transactions/lock.tpl');
