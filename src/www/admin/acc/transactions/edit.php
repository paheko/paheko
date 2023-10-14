<?php

namespace Paheko;

use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Files\File;
use Paheko\Accounting\Projects;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
	throw new UserException('Cette écriture n\'existe pas');
}

$transaction->assertCanBeModified();

$year = Years::get($transaction->id_year);
$chart = $year->chart();
$accounts = $chart->accounts();

$csrf_key = 'acc_transaction_edit_' . $transaction->id();

$tpl->assign('chart', $chart);

$form->runIf('save', function() use ($transaction, $session) {
	$transaction->importFromNewForm();
	$transaction->save();

	// Link members
	if (null !== f('users') && is_array(f('users'))) {
		$transaction->updateLinkedUsers(array_keys(f('users')));
	}
	else {
		// Remove all
		$transaction->deleteLinkedUsers();
	}
}, $csrf_key, '!acc/transactions/details.php?id=' . $transaction->id());

$types_accounts = [];

$lines = null;

$form->runIf(f('lines') !== null, function () use (&$lines) {
	$lines = Transaction::getFormLines();
});

if (null === $lines) {
	$lines = $transaction->getLinesWithAccounts();
}

$amount = $transaction->getLinesCreditSum();
$types_details = $transaction->getTypesDetails();
$id_project = $transaction->getProjectId();
$has_reconciled_lines = $transaction->hasReconciledLines();

$tpl->assign(compact('csrf_key', 'transaction', 'lines', 'amount', 'has_reconciled_lines', 'types_details', 'id_project'));

$tpl->assign('chart_id', $chart->id());
$tpl->assign('projects', Projects::listAssoc());
$tpl->assign('linked_users', $transaction->listLinkedUsersAssoc());

$tpl->display('acc/transactions/edit.tpl');
