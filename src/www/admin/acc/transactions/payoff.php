<?php
namespace Paheko;

use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Files\File;
use Paheko\Accounting\Projects;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;

use KD2\DB\Date;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE);

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$sources = [];

foreach ((array)qg('for') as $id) {
	$id = (int) $id;
	$t = Transactions::get($id);

	if (!$t) {
		throw new UserException('Écriture inconnue : ' . $id);
	}
}

// Quick pay-off for debts and credits, directly from a debt/credit details page
$payoff = Transactions::createPayoffFrom($sources);
$transaction = $payoff->transaction;

$amount = $payoff->amount;

$chart = $current_year->chart();
$accounts = $chart->accounts();

$date = new Date;

if ($session->get('acc_last_date')) {
	$date = Date::createFromFormat('!Y-m-d', $session->get('acc_last_date'));
}

if (!$date || ($date < $current_year->start_date || $date > $current_year->end_date)) {
	$date = $current_year->start_date;
}

$transaction->date = $date;

$form->runIf('save', function () use ($transaction, $session, $current_year) {
	$transaction->importFromPayoffForm();
	$transaction->id_year = $current_year->id();
	$transaction->id_creator = $session->getUser()->id;
	$transaction->save();

	if (f('mark_paid')) {
		$transaction->related()->markPaid();
		$transaction->related()->save();
	}

	 // Link members
	if (null !== f('users') && is_array(f('users'))) {
		$transaction->updateLinkedUsers(array_keys(f('users')));
	}

	$session->set('acc_last_date', $transaction->date->format('Y-m-d'));
	$session->save();

	Utils::redirect('!acc/transactions/details.php?created&id=' . $transaction->id());
}, 'acc_transaction_new');

$id_project = $payoff_for->id_project;

$tpl->assign(compact('transaction', 'payoff_for', 'amount', 'id_project'));
$tpl->assign('payoff_targets', implode(':', [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING]));

$tpl->assign('chart_id', $chart->id());
$tpl->assign('linked_users', $original->listLinkedUsersAssoc());

$tpl->assign('projects', Projects::listAssoc());
$tpl->display('acc/transactions/payoff.tpl');
