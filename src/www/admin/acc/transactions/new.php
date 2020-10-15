<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ECRITURE);

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$chart = $current_year->chart();
$accounts = $chart->accounts();

$transaction = new Transaction;
$lines = [[], []];
$amount = 0;
$payoff_for = null;

if ($id = f('payoff_for')) {
	$payoff_for = $transaction->payOffFrom($id);
	$amount = $payoff_for->sum();
}

if (f('save') && $form->check('acc_transaction_new')) {
	try {
		$transaction->id_year = $current_year->id();
		$transaction->importFromNewForm();
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

		$session->set('acc_last_date', f('date'));

		Utils::redirect(Utils::getSelfURL(false) . '?ok=' . $transaction->id());
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}

$tpl->assign('date', $session->get('acc_last_date') ?: $current_year->start_date->format('d/m/Y'));
$tpl->assign(compact('transaction', 'payoff_for', 'amount', 'lines'));
$tpl->assign('payoff_targets', implode(':', [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING]));
$tpl->assign('ok', (int) qg('ok'));

$tpl->assign('types', Transaction::getTypesDetails());
$tpl->assign('chart_id', $chart->id());

$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->display('acc/transactions/new.tpl');
