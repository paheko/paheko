<?php

namespace Garradin;

use Garradin\Entities\Accounting\Transaction;
use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

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

$tpl->assign('chart', $chart);

$rules = [
	'lines' => 'array|required',
];

if (f('save') && $form->check('acc_edit_' . $transaction->id(), $rules)) {
	try {
		$transaction->importFromEditForm();
		$transaction->save();

		// Append file
		if (!empty($_FILES['file']['name'])) {
			$file = Fichiers::upload($_FILES['file']);
			$file->linkTo(Fichiers::LIEN_COMPTA, $transaction->id());
		}

		// Link members
		if (null !== f('users') && is_array(f('users'))) {
			$transaction->updateLinkedUsers(array_keys(f('users')));
		}
		else {
			// Remove all
			$transaction->updateLinkedUsers([]);
		}

		Utils::redirect(ADMIN_URL . 'acc/transactions/details.php?id=' . $transaction->id());
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}

$types_accounts = [];
$lines = [];

if (!empty($_POST['lines']) && is_array($_POST['lines'])) {
	$lines = Utils::array_transpose($_POST['lines']);

	foreach ($lines as &$line) {
		$line = (object) $line;
		$line->credit = Utils::moneyToInteger($line->credit);
		$line->debit = Utils::moneyToInteger($line->debit);
	}
}
else {
	$lines = $transaction->getLinesWithAccounts();

	foreach ($lines as $k => &$line) {
		$line->account = [$line->id_account => sprintf('%s — %s', $line->account_code, $line->account_name)];
	}
}

$has_reconciled_lines = true;

array_walk($lines, function ($l) use (&$has_reconciled_lines) {
	if (!empty($line->reconciled)) {
		$has_reconciled_lines = true;
	}
});

$first_line = $transaction->getFirstLine();

if ($transaction->type != Transaction::TYPE_ADVANCED) {
	$types_accounts = $transaction->getTypesAccounts();
}

$amount = $transaction->getLinesCreditSum();

$tpl->assign(compact('transaction', 'lines', 'types_accounts', 'amount', 'first_line', 'has_reconciled_lines'));

$tpl->assign('types_details', Transaction::getTypesDetails());
$tpl->assign('chart_id', $chart->id());
$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->assign('linked_users', $transaction->listLinkedUsersAssoc());

$tpl->display('acc/transactions/edit.tpl');
