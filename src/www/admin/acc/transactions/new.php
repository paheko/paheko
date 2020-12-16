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
$payoff_for = qg('payoff_for') ?: f('payoff_for');

// Quick pay-off for debts and credits, directly from a debt/credit details page
if ($id = $payoff_for) {
	$payoff_for = $transaction->payOffFrom($id);

	if (!$payoff_for) {
		throw new UserException('Écriture inconnue');
	}

	$amount = $payoff_for->sum;
}

// Quick transaction from an account journal page
if ($id = qg('account')) {
	$account = $accounts::get($id);

	if (!$account || $account->id_chart != $current_year->id_chart) {
		throw new UserException('Ce compte ne correspond pas à l\'exercice comptable ou n\'existe pas');
	}

	$transaction->type = Transaction::getTypeFromAccountType($account->type);
	$key = sprintf('account_%d_%d', $transaction->type, 0);

	if (!isset($_POST[$key])) {
		$lines[0]['account'] = $_POST[$key] = [$account->id => sprintf('%s — %s', $account->code, $account->label)];
	}
}
elseif (!empty($_POST['lines']) && is_array($_POST['lines'])) {
	$lines = Utils::array_transpose($_POST['lines']);

	foreach ($lines as &$line) {
		$line['credit'] = Utils::moneyToInteger($line['credit']);
		$line['debit'] = Utils::moneyToInteger($line['debit']);
	}
}

if (f('save') && $form->check('acc_transaction_new')) {
	try {
		$transaction->id_year = $current_year->id();
		$transaction->importFromNewForm();
		$transaction->id_creator = $session->getUser()->id;
		$transaction->save();

		// Append fileTYPE_ANALYTICAL
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

$date = new \DateTime;

if ($session->get('acc_last_date')) {
	$date = \DateTime::createFromFormat('!d/m/Y', $session->get('acc_last_date'));
}

if (!$date || ($date < $current_year->start_date || $date > $current_year->end_date)) {
	$date = $current_year->start_date;
}

$tpl->assign('date', $date->format('d/m/Y'));
$tpl->assign(compact('transaction', 'payoff_for', 'amount', 'lines'));
$tpl->assign('payoff_targets', implode(':', [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING]));
$tpl->assign('ok', (int) qg('ok'));

$tpl->assign('types_details', Transaction::getTypesDetails());
$tpl->assign('chart_id', $chart->id());

$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->display('acc/transactions/new.tpl');
