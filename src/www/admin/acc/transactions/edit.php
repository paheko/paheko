<?php

namespace Garradin;

use Garradin\Entities\Accounting\Transaction;
use Garradin\Entities\Files\File;
use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
	throw new UserException('Cette écriture n\'existe pas');
}

if ($transaction->validated) {
	throw new UserException('Cette écriture est validée et ne peut être modifiée');
}

$year = Years::get($transaction->id_year);

if ($year->closed) {
	throw new UserException('Cette écriture ne peut être modifiée car elle appartient à un exercice clôturé');
}

$chart = $year->chart();
$accounts = $chart->accounts();

$csrf_key = 'acc_transaction_edit_' . $transaction->id();

$tpl->assign('chart', $chart);
//var_dump('<pre>', $_POST); exit;

$form->runIf('save', function() use ($transaction, $session) {
	$transaction->importFromNewForm();
	$transaction->save();

	// Link members
	if (null !== f('users') && is_array(f('users'))) {
		$transaction->updateLinkedUsers(array_keys(f('users')));
	}
	else {
		// Remove all
		$transaction->updateLinkedUsers([]);
	}
}, $csrf_key, '!acc/transactions/details.php?id=' . $transaction->id());

$types_accounts = [];
$lines = isset($_POST['lines']) ? Transaction::getFormLines() : $transaction->getLinesWithAccounts();

$amount = $transaction->getLinesCreditSum();
$types_details = $transaction->getTypesDetails();
$id_analytical = $transaction->getAnalyticalId();
$has_reconciled_lines = $transaction->hasReconciledLines();

$tpl->assign(compact('csrf_key', 'transaction', 'lines', 'amount', 'has_reconciled_lines', 'types_details', 'id_analytical'));

$tpl->assign('chart_id', $chart->id());
$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->assign('linked_users', $transaction->listLinkedUsersAssoc());

$tpl->display('acc/transactions/new.tpl');
