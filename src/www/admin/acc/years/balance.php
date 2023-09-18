<?php
namespace Paheko;

use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Accounting\Reports;
use Paheko\Accounting\Years;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

$csrf_key = 'acc_years_balance_' . $year->id();
$accounts = $year->accounts();

$form->runIf('save', function () use ($year) {
	$db = DB::getInstance();
	// Fail everything if appropriation failed
	$db->begin();

	$year->deleteOpeningBalance();

	$transaction = new Transaction;
	$transaction->id_creator = Session::getUserId();
	$transaction->importFromBalanceForm($year);
	$transaction->save();

	if (f('appropriation')) {
		// (affectation du résultat)
		$t2 = Years::makeAppropriation($year);

		if ($t2) {
			$t2->id_creator = $transaction->id_creator;
			$t2->save();
		}
	}

	$db->commit();

	if (f('appropriation')) {
		Utils::redirect('!acc/reports/journal.php?year=' . $year->id());
	}

	Utils::redirect('!acc/transactions/details.php?id=' . $transaction->id());
}, $csrf_key);


$previous_year = null;
$year_selected = f('from_year') !== null;
$chart_change = false;
$lines = [[]];
$years = Years::list(true, $year->id);

// Empty balance
if (!count($years) || f('from_year') === '') {
	$previous_year = 0;
}
elseif (null !== f('from_year')) {
	$previous_year = (int)f('from_year');
	$previous_year = Years::get($previous_year);

	if (!$previous_year) {
		throw new UserException('Année précédente invalide');
	}
}

$matching_accounts = null;

if ($previous_year) {
	$lines = Reports::getAccountsBalances(['year' => $previous_year->id(), 'exclude_position' => [Account::EXPENSE, Account::REVENUE]]);

	if ($previous_year->id_chart != $year->id_chart) {
		$chart_change = true;
		$codes = [];

		foreach ($lines as $line) {
			$codes[] = $line->code;
		}

		$matching_accounts = $accounts->listForCodes($codes);
	}

	// Append result
	$result = Reports::getResult(['year' => $previous_year->id()]);

	if ($result > 0) {
		$account = $accounts->getSingleAccountForType(Account::TYPE_POSITIVE_RESULT);
	}
	else {
		$account = $accounts->getSingleAccountForType(Account::TYPE_NEGATIVE_RESULT);
	}

	if (!$account) {
		$account = (object) [
			'id' => null,
			'code' => null,
			'label' => null,
		];
	}

	$lines[] = (object) [
		'balance'   => $result,
		'id'    => $account->id,
		'code'  => $account->code,
		'label' => $account->label,
		'is_debt' => $result < 0,
	];

	foreach ($lines as $k => &$line) {
		$line->credit = !$line->is_debt ? abs($line->balance) : 0;
		$line->debit = $line->is_debt ? abs($line->balance) : 0;

		if ($chart_change) {
			if ($matching_accounts && array_key_exists($line->code, $matching_accounts)) {
				$acc = $matching_accounts[$line->code];
				$line->account_selector = [$acc->id => sprintf('%s — %s', $acc->code, $acc->label)];
			}
		}
		else {
			$line->account_selector = $line->id ? [$line->id => sprintf('%s — %s', $line->code, $line->label)] : null;
		}

		$line = (array) $line;
	}

	unset($line);
}


if (!empty($_POST['lines']) && is_array($_POST['lines'])) {
	$lines = Transaction::getFormLines();
}

$appropriation_account = $accounts->getSingleAccountForType(Account::TYPE_APPROPRIATION_RESULT);
$can_appropriate = $accounts->getIdForType(Account::TYPE_NEGATIVE_RESULT) && $accounts->getIdForType(Account::TYPE_POSITIVE_RESULT);
$has_balance = $year->hasOpeningBalance();

$tpl->assign(compact('lines', 'years', 'chart_change', 'previous_year', 'year_selected', 'year', 'csrf_key', 'can_appropriate', 'appropriation_account', 'has_balance'));

$tpl->display('acc/years/balance.tpl');
