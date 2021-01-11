<?php
namespace Garradin;

use Garradin\Accounting\Accounts;
use Garradin\Accounting\Transactions;
use Garradin\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$account = Accounts::get((int)qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

$checked = f('deposit') ?: [];

$journal = $account->getDepositJournal(CURRENT_YEAR_ID, $checked);
$transaction = new Transaction;
$transaction->id_year = CURRENT_YEAR_ID;
$transaction->id_creator = $session->getUser()->id;

$rules = [
	'deposit' => 'array|required',
];

// Enregistrement des cases cochées
if (f('save') && $form->check('acc_deposit_' . $account->id, $rules))
{
	try {
		$transaction->importFromDepositForm();
		Transactions::saveDeposit($transaction, $journal, f('deposit'));
		Utils::redirect(ADMIN_URL . 'acc/transactions/details.php?id=' . $transaction->id());
	}
	catch (UserException $e) {
		$journal = $account->getDepositJournal(CURRENT_YEAR_ID);
		$form->addError($e->getMessage());
	}
}

$date = new \DateTime;

if ($date > $current_year->end_date) {
	$date = $current_year->end_date;
}

$target = $account::TYPE_BANK;

$tpl->assign(compact(
	'account',
	'journal',
	'date',
	'target',
	'checked'
));

$tpl->display('acc/accounts/deposit.tpl');
