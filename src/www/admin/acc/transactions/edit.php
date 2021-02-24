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

$tpl->assign('chart', $chart);

$rules = [
	'lines' => 'array|required',
];

if (f('save') && $form->check('acc_edit_' . $transaction->id(), $rules)) {
	try {
		$transaction->importFromEditForm();
		$transaction->save();

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

	unset($line);
}
else {
	$lines = $transaction->getLinesWithAccounts();

	foreach ($lines as $k => &$line) {
		$line->account = [$line->id_account => sprintf('%s — %s', $line->account_code, $line->account_name)];
	}

	unset($line);
}

$has_reconciled_lines = false;

foreach ($lines as $line) {
	if (!empty($line->reconciled)) {
		$has_reconciled_lines = true;
		break;
	}
}

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
