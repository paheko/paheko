<?php
namespace Paheko;

use Paheko\Accounting\Accounts;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE);

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

if (!$current_year->isOpen()) {
	Utils::redirect(ADMIN_URL . 'acc/years/select.php?msg=CLOSED&from=' . rawurlencode(Utils::getSelfURI()));
}

$account = Accounts::get((int)qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

$checked = array_keys($_POST['deposit'] ?? []);
$only_this_year = boolval($_GET['only'] ?? true);

$journal = $account->getDepositJournal(CURRENT_YEAR_ID, $only_this_year, $checked);
$transaction = new Transaction;
$transaction->id_year = CURRENT_YEAR_ID;
$transaction->setCreatorFromSession($session);
$csrf_key = 'acc_deposit_' . $account->id();

$form->runIf('mark', function () use ($account, $checked) {
	if (!count($checked)) {
		throw new UserException('Aucune ligne n\'a été cochée.');
	}

	$account->markTransactionsLinesAsDeposited($checked);
}, $csrf_key, '!acc/accounts/deposit.php?id=' . $account->id());

$form->runIf('save', function () use ($account, $checked, $transaction, $journal) {
	if (!count($checked)) {
		throw new UserException('Aucune ligne n\'a été cochée, impossible de créer un dépôt. Peut-être vouliez-vous saisir un virement ?');
	}

	$transaction->importFromDepositForm();
	Transactions::saveDeposit($account, $transaction, $journal->iterate(), $checked);

	Utils::redirect(ADMIN_URL . 'acc/transactions/details.php?id=' . $transaction->id());
}, $csrf_key);

// Uncheck everything if there was an error
if ($form->hasErrors()) {
	$journal = $account->getDepositJournal(CURRENT_YEAR_ID, $only_this_year);
}

$date = new \DateTime;

if ($date > $current_year->end_date) {
	$date = $current_year->end_date;
}

$types = $account::TYPE_BANK;

$missing_balance = $account->getDepositMissingBalance(CURRENT_YEAR_ID, $only_this_year);
$has_transactions_from_other_years = $account->hasMissingDepositsFromOtherYears(CURRENT_YEAR_ID);

$journal->loadFromQueryString();

$tpl->assign(compact(
	'has_transactions_from_other_years',
	'only_this_year',
	'account',
	'journal',
	'date',
	'types',
	'checked',
	'missing_balance',
	'transaction',
	'csrf_key',
));

$tpl->display('acc/accounts/deposit.tpl');
