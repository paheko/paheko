<?php
namespace Paheko;

use Paheko\Accounting\Accounts;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

if (!$current_year->isOpen()) {
	Utils::redirect(ADMIN_URL . 'acc/years/select.php?msg=CLOSED&from=' . rawurlencode(Utils::getSelfURI()));
}

$account = Accounts::get((int)qg('id'));
$year_id = intval($_GET['from_year'] ?? 0);
$year = Years::get($year_id);

if (!$account || !$year) {
	throw new UserException("Le compte demandé n'existe pas.");
}

$checked = f('deposit') ?: [];

$journal = $account->getDepositJournal($year_id, $checked);
$transaction = new Transaction;
$transaction->id_year = CURRENT_YEAR_ID;
$transaction->id_creator = $session->getUser()->id;

$form->runIf('save', function () use ($checked, $transaction, $journal) {
	if (!count($checked)) {
		throw new UserException('Aucune ligne n\'a été cochée, impossible de créer un dépôt. Peut-être vouliez-vous saisir un virement ?');
	}

	$transaction->importFromDepositForm();
	Transactions::saveDeposit($transaction, $journal->iterate(), $checked);

	Utils::redirect(ADMIN_URL . 'acc/transactions/details.php?id=' . $transaction->id());
}, 'acc_deposit_' . $account->id());

// Uncheck everything if there was an error
if ($form->hasErrors()) {
	$journal = $account->getDepositJournal($year_id);
}

$date = new \DateTime;

if ($date > $current_year->end_date) {
	$date = $current_year->end_date;
}

$types = $account::TYPE_BANK;

$missing_balance = $account->getDepositMissingBalance($year_id);

$journal->loadFromQueryString();

$tpl->assign(compact(
	'account',
	'journal',
	'date',
	'types',
	'checked',
	'missing_balance',
	'transaction',
	'year'
));

$tpl->display('acc/accounts/deposit.tpl');
