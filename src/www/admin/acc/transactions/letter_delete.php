<?php

namespace Paheko;

use Paheko\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$current_year->assertCanBeModified();

$csrf_key = 'acc_delete_letter';

$form->runIf('delete', function () use ($current_year) {
	Transactions::deleteLetter($current_year->id(), $_POST['letter'] ?? '');
}, $csrf_key, '!acc/');

$tpl->assign(compact('csrf_key'));

$tpl->display('acc/transactions/letter_delete.tpl');
