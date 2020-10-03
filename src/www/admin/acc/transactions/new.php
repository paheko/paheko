<?php
namespace Garradin;

use Garradin\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ECRITURE);

$chart = $year->chart();
$accounts = $chart->accounts();

$transaction = new Transaction;
$lines = [[], []];

if (f('save') && $form->check('acc_transaction_new')) {
	try {
		$transaction->id_year = $year->id();
		$transaction->importFromNewForm($chart->id());
		$transaction->id_creator = $session->getUser()->id;
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

		Utils::redirect(Utils::getSelfURL(false) . '?ok=' . $transaction->id());
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}

$tpl->assign('date', $session->get('context_compta_date') ?: false);
$tpl->assign('ok', (int) qg('ok'));

$tpl->assign('lines', $lines);

$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->display('acc/transactions/new.tpl');
