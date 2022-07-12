<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Entities\Files\File;
use Garradin\Accounting\AssistedReconciliation;
use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE);

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$chart = $current_year->chart();
$accounts = $chart->accounts();

$transaction = new Transaction;
$lines = [[], []];
$amount = 0;
$types_accounts = null;
$id_analytical = null;

// Quick-fill transaction from query parameters
if (qg('a')) {
	$amount = Utils::moneyToInteger(qg('a'));
}

if (qg('l')) {
	$transaction->label = qg('l');
}

if (qg('d')) {
	$transaction->date = new \DateTime(qg('d'));
}

if (qg('t')) {
	$transaction->type = (int) qg('t');
}

// Duplicate transaction
if (qg('copy')) {
	$old = Transactions::get((int)qg('copy'));

	if (!$old) {
		throw new UserException('Cette écriture n\'existe pas (ou plus).');
	}

	$transaction = $old->duplicate($current_year);
	$lines = $transaction->getLinesWithAccounts(true);
	$id_analytical = $old->getAnalyticalId();
	$amount = $transaction->getLinesCreditSum();
	$types_accounts = $transaction->getTypesAccounts();
	$transaction->resetLines();

	foreach ($lines as $k => &$line) {
		$line->account = [$line->id_account => sprintf('%s — %s', $line->account_code, $line->account_name)];
	}

	unset($line);
}

// Set last used date
if (empty($transaction->date) && $session->get('acc_last_date') && $date = \DateTime::createFromFormat('!Y-m-d', $session->get('acc_last_date'))) {
	$transaction->date = $date;
}
// Set date of the day if no date was set
elseif (empty($transaction->date)) {
	$transaction->date = new \DateTime;
}

// Make sure the date cannot be outside of the current year
if ($transaction->date < $current_year->start_date || $transaction->date > $current_year->end_date) {
	$transaction->date = $current_year->start_date;
}

// Quick transaction from an account journal page
if ($id = qg('account')) {
	$account = $accounts::get($id);

	if (!$account || $account->id_chart != $current_year->id_chart) {
		throw new UserException('Ce compte ne correspond pas à l\'exercice comptable ou n\'existe pas');
	}

	$transaction->type = Transaction::getTypeFromAccountType($account->type);
	$index = $transaction->type == Transaction::TYPE_DEBT || $transaction->type == Transaction::TYPE_CREDIT ? 1 : 0;
	$key = sprintf('account_%d_%d', $transaction->type, $index);

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

$form->runIf('save', function () use ($transaction, $session, $current_year) {
	$transaction->importFromNewForm();
	$transaction->id_year = $current_year->id();
	$transaction->id_creator = $session->getUser()->id;
	$transaction->save();

	 // Link members
	if (null !== f('users') && is_array(f('users'))) {
		$transaction->updateLinkedUsers(array_keys(f('users')));
	}

	$session->set('acc_last_date', $transaction->date->format('Y-m-d'));

	Utils::redirect(sprintf('!acc/transactions/details.php?id=%d&created', $transaction->id()));
}, 'acc_transaction_new');

$tpl->assign(compact('transaction', 'amount', 'lines', 'types_accounts', 'id_analytical'));

$tpl->assign('types_details', Transaction::getTypesDetails());
$tpl->assign('chart_id', $chart->id());

$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->display('acc/transactions/new.tpl');
