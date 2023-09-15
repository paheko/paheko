<?php
namespace Paheko;

use Paheko\Entity;
use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Files\File;
use Paheko\Accounting\Accounts;
use Paheko\Accounting\Projects;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Users\Users;

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
$linked_services = [];

$lines = [[], []];

// Quick-fill transaction from query parameters
// a = amount, in single currency units
if (qg('a')) {
	$amount = Utils::moneyToInteger(qg('a'));
}

// a0 = Amount, in cents
if (qg('a0')) {
	$amount = (int)qg('a0');
}

// l = label
if (qg('l')) {
	$transaction->label = qg('l');
}

// r = reference
if (qg('r')) {
	$transaction->reference = qg('r');
}

// dt = date
if (qg('dt')) {
	$transaction->date = Entity::filterUserDateValue(qg('dt'));
}

// t = type
if (null !== qg('t')) {
	$transaction->type = (int) qg('t');
}

// ab = Bank/cash account
if (qg('ab') && ($a = $accounts->getWithCode(qg('ab')))
	&& in_array($a->type, [$a::TYPE_BANK, $a::TYPE_CASH, $a::TYPE_OUTSTANDING])) {
	$transaction->setDefaultAccount($transaction::TYPE_REVENUE, 'debit', $a->id);
	$transaction->setDefaultAccount($transaction::TYPE_EXPENSE, 'credit', $a->id);
	$transaction->setDefaultAccount($transaction::TYPE_TRANSFER, 'debit', $a->id);
}

// ar = Revenue account
if (qg('ar') && ($a = $accounts->getWithCode(qg('ar')))
	&& $a->type == $a::TYPE_REVENUE) {
	$transaction->setDefaultAccount($transaction::TYPE_REVENUE, 'credit', $a->id);
	$transaction->setDefaultAccount($transaction::TYPE_CREDIT, 'credit', $a->id);
}

// ae = Expense account
if (qg('ae') && ($a = $accounts->getWithCode(qg('ae')))
	&& $a->type == $a::TYPE_EXPENSE) {
	$transaction->setDefaultAccount($transaction::TYPE_EXPENSE, 'debit', $a->id);
	$transaction->setDefaultAccount($transaction::TYPE_DEBT, 'debit', $a->id);
}

// at = Transfer account
if (qg('at') && ($a = $accounts->getWithCode(qg('at')))
	&& $a->type == $a::TYPE_BANK) {
	$transaction->setDefaultAccount($transaction::TYPE_TRANSFER, 'credit', $a->id);
}

// a3 = Third-party account
if (qg('a3') && ($a = $accounts->getWithCode(qg('a3')))
	&& $a->type == $a::TYPE_THIRD_PARTY) {
	$transaction->setDefaultAccount($transaction::TYPE_CREDIT, 'debit', $a->id);
	$transaction->setDefaultAccount($transaction::TYPE_DEBT, 'credit', $a->id);
}

// Pre-fill from lllines
if (($ll = qg('ll')) && is_array($ll)) {
	$lines = [];
	foreach ($ll as $l) {
		$lines[] = [
			'debit'            => $l['d0'] ?? Utils::moneyToInteger($l['d'] ?? ''),
			'credit'           => $l['c0'] ?? Utils::moneyToInteger($l['c'] ?? ''),
			'account_selector' => Accounts::getSelectorFromCode($l['a'] ?? null),
			'label'            => $l['l'] ?? null,
			'reference'        => $l['r'] ?? null,
		];
	}

	// Make sure we have at least two lines
	$lines = array_merge($lines, array_fill(0, max(0, 2 - count($lines)), []));
}

$form->runIf(f('lines') !== null, function () use (&$lines) {
	$lines = Transaction::getFormLines();
});

if (qg('u')) {
	$linked_users = [];
	$i = 0;

	foreach ((array) qg('u') as $key => $value) {
		if ($key != $i++ && $value) {
			$id = (int) $key;
			$linked_services[$id] = (int) $value;
		}
		else {
			$id = (int) $value;
		}

		$name = Users::getName($id);

		if ($name) {
			$linked_users[$id] = $name;
		}
	}
}

$types_details = $transaction->getTypesDetails();

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

$transaction->id_year = $current_year->id();

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

$form->runIf('save', function () use ($transaction, $session, $current_year, $linked_services) {
	$transaction->importFromNewForm();
	$transaction->id_creator = $session->getUser()->id;
	$transaction->save();

	 // Link members
	if (null !== f('users') && is_array(f('users'))) {
		$users = f('users');

		foreach ($linked_services as $user_id => $service_id) {
			// Maybe the user was deleted from the list manually
			if (array_key_exists($user_id, $users)) {
				// Link service_user relationship to transaction
				$transaction->linkToUser($user_id, $service_id);
			}

			unset($users[$user_id]);
		}

		if (count($users)) {
			$transaction->updateLinkedUsers(array_keys($users));
		}
	}

	$session->set('acc_last_date', $transaction->date->format('Y-m-d'));
	$session->save();

	if (array_key_exists('_dialog', $_GET)) {
		Utils::reloadParentFrame();
		return;
	}

	Utils::redirect(sprintf('!acc/transactions/details.php?id=%d&created', $transaction->id()));
}, $csrf_key);

$tpl->assign(compact('csrf_key', 'transaction', 'amount', 'lines', 'id_project', 'types_details', 'linked_users'));

$tpl->assign('chart', $chart);
$tpl->assign('projects', Projects::listAssocWithEmpty());

$tpl->display('acc/transactions/new.tpl');
