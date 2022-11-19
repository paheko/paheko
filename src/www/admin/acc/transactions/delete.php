<?php

namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$transaction = Transactions::get((int) qg('id'));

$transaction->assertCanBeModified();

$csrf_id = 'acc_delete_' . $transaction->id;

$form->runIf('delete', function () use ($transaction) {
	$transaction->delete();
}, $csrf_key, '!acc/');

$tpl->assign(compact('transaction'));

$tpl->display('acc/transactions/delete.tpl');
