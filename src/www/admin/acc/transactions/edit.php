<?php

namespace Garradin;

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
		$_POST['type'] = 'advanced';

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

$tpl->assign('transaction', $transaction);

$lines = $transaction->getLinesWithAccounts();
$lines_accounts = [];

foreach ($lines as $k => $line) {
	$lines_accounts[$k] = [$line->id_account => sprintf('%s — %s', $line->account_code, $line->account_name)];
}

$tpl->assign('lines', $lines);
$tpl->assign('lines_accounts', $lines_accounts);
$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->assign('linked_users', $transaction->listLinkedUsersAssoc());

$tpl->display('acc/transactions/edit.tpl');
