<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Entities\Files\File;
use Garradin\Accounting\Projects;
use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

use KD2\DB\Date;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE);

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$chart = $current_year->chart();
$accounts = $chart->accounts();

$csrf_key = 'acc_transaction_new';
$transaction = new Transaction;
$amount = 0;
$id_project = null;
$linked_users = null;
$lines = isset($_POST['lines']) ? Transaction::getFormLines() : [[], []];
$types_details = $transaction->getTypesDetails();

// Quick-fill transaction from query parameters
if (qg('a')) {
	$amount = Utils::moneyToInteger(qg('a'));
}

if (qg('l')) {
	$transaction->label = qg('l');
}

if (qg('d')) {
	$transaction->date = new Date(qg('d'));
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

	if (empty($_POST)) {
		$lines = $transaction->getLinesWithAccounts();
		$types_details = $transaction->getTypesDetails();
	}

	$id_project = $old->getProjectId();
	$amount = $transaction->getLinesCreditSum();
	$linked_users = $old->listLinkedUsersAssoc();

	$tpl->assign('duplicate_from', $old->id());
}

// Set last used date
if (empty($transaction->date) && $session->get('acc_last_date') && $date = Date::createFromFormat('!Y-m-d', $session->get('acc_last_date'))) {
	$transaction->date = $date;
}
// Set date of the day if no date was set
elseif (empty($transaction->date)) {
	$transaction->date = new Date;
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
	$s = [$account->id => sprintf('%s — %s', $account->code, $account->label)];

	if ($transaction->type) {
		$types_details[$transaction->type]->accounts[$index]->selector_value = $s;
	}
	else {
		$lines = [['account_selector' => $s], []];
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
}, $csrf_key);

$tpl->assign(compact('csrf_key', 'transaction', 'amount', 'lines', 'id_project', 'types_details', 'linked_users'));

$tpl->assign('chart_id', $chart->id());
$tpl->assign('projects', Projects::listAssocWithEmpty());

$tpl->display('acc/transactions/new.tpl');
