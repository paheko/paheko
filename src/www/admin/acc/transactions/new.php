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
use Paheko\UserTemplate\Modules;

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
else {
	$defaults = $transaction->setDefaultsFromQueryString($accounts);

	if (null !== $defaults) {
		extract($defaults);
	}
}

$form->runIf(f('lines') !== null, function () use (&$lines) {
	$lines = Transaction::getFormLines();
});

// Keep this line here, as the transaction can be overwritten by copy
$transaction->id_year = $current_year->id();
$types_details = $transaction->getTypesDetails();

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

$form->runIf('save', function () use ($transaction, $session, $linked_services) {
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

$projects = Projects::listAssoc();
$variables = compact('csrf_key', 'transaction', 'amount', 'lines', 'id_project', 'types_details', 'linked_users', 'chart', 'projects');

$tpl->assign($variables);

$tpl->assign('snippets', Modules::snippetsAsString(Modules::SNIPPET_BEFORE_NEW_TRANSACTION, $variables));

$tpl->display('acc/transactions/new.tpl');
