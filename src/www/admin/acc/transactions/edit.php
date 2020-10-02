<?php

namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
	throw new UserException('Cette écriture n\'existe pas');
}

$chart = $year->chart();
$accounts = $chart->accounts();

$rules = [
	'lines' => 'array|required',
];

if (f('save') && $form->check('acc_edit_' . $transaction->id(), $rules)) {
	try {
		$lines = f('lines');
		$max = count($lines['label']);

		if ($max != count($lines['debit'])
			|| $max != count($lines['credit'])
			|| $max != count($lines['reference'])
			|| $max != count($lines['account']))
		{
			throw new UserException('Erreur dans les lignes de l\'écriture');
		}

		$out = [];

		// Reorder the POST data as a proper array
		for ($i = 0; $i < $max; $i++) {
			$out[] = [
				'debit'      => $lines['debit'][$i],
				'credit'     => $lines['credit'][$i],
				'reference'  => $lines['reference'][$i],
				'label'      => $lines['label'][$i],
				'account'    => $lines['account'][$i],
				'id_analytical' => !empty($lines['id_analytical'][$i]) ? $lines['id_analytical'][$i] : null,
			];
		}

		$_POST['type'] = 'advanced';
		$_POST['lines'] = $lines = $out;

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
	$lines_accounts[$k] = [$line->id_account => sprintf('%s - %s', $line->account_code, $line->account_name)];
}

$tpl->assign('lines', $lines);
$tpl->assign('lines_accounts', $lines_accounts);
$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->assign('linked_users', $transaction->listLinkedUsersAssoc());

$tpl->display('acc/transactions/edit.tpl');
