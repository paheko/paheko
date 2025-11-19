<?php
namespace Paheko;

use Paheko\Accounting\Accounts;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

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

if (isset($_POST['deposit']) && is_string($_POST['deposit'])) {
	$checked = json_decode($_POST['deposit'], true);
}
elseif (isset($_POST['deposit']) && is_array($_POST['deposit'])) {
	$checked = array_keys($_POST['deposit']);
}

$checked ??= [];
$only_this_year = boolval($_GET['only'] ?? true);

$journal = $account->getDepositJournal(CURRENT_YEAR_ID, $only_this_year, $checked);
$transaction = new Transaction;
$transaction->id_year = CURRENT_YEAR_ID;
$transaction->id_creator = $session->getUser()->id;
$csrf_key = 'acc_deposit_' . $account->id();
$types = $account::TYPE_BANK;

$date = new \DateTime;

if ($date > $current_year->end_date) {
	$date = $current_year->end_date;
}

$tpl->assign(compact(
	'account',
	'checked',
	'csrf_key',
	'date',
	'types',
));

if (!empty($_POST['mark']) || !empty($_POST['create'])) {
	if (!count($checked)) {
		throw new UserException('Aucune ligne n\'a été cochée, impossible de créer un dépôt. Peut-être vouliez-vous saisir un virement ?');
	}

	$form->runIf('confirm_mark', function () use ($account, $checked) {
		$account->markLinesAsDeposited($checked);
	}, $csrf_key, '!acc/accounts/deposit.php?marked&id=' . $account->id());

	$form->runIf('save', function () use ($account, $checked, $transaction, $journal) {
		$transaction->importFromDepositForm();
		Transactions::saveDeposit($account, $transaction, $journal->iterate(), $checked);

		Utils::redirect(ADMIN_URL . 'acc/transactions/details.php?id=' . $transaction->id());
	}, $csrf_key);

	$total = 0;

	foreach ($journal->iterate() as $item) {
		if (in_array($item->id_line, $checked)) {
			$total += $item->debit;
		}
	}

	$tpl->assign('mode', !empty($_POST['mark']) ? 'mark' : 'deposit');
	$tpl->assign('checked_json', json_encode($checked));
	$tpl->assign(compact('total'));
	$tpl->display('acc/accounts/deposit_form.tpl');
}
else {
	// Uncheck everything if there was an error
	if ($form->hasErrors()) {
		$journal = $account->getDepositJournal(CURRENT_YEAR_ID, $only_this_year);
	}

	$missing_balance = $account->getDepositMissingBalance(CURRENT_YEAR_ID, $only_this_year);
	$has_transactions_from_other_years = $account->hasMissingDepositsFromOtherYears(CURRENT_YEAR_ID);

	$journal->loadFromQueryString();

	$tpl->assign(compact(
		'has_transactions_from_other_years',
		'only_this_year',
		'journal',
		'missing_balance',
	));

	$tpl->display('acc/accounts/deposit.tpl');
}
