<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Accounting\Reports;
use Garradin\Accounting\Years;
use Garradin\Membres\Session;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

$csrf_key = 'acc_years_balance_' . $year->id();

$form->runIf('save', function () use ($year) {
	$transaction = new Transaction;
	$transaction->id_creator = Session::getInstance()->getUser()->id;
	$transaction->importFromBalanceForm($year);
	$transaction->save();

	if (f('appropriation')) {
		// (affectation du résultat)
		$t2 = Years::makeAppropriation($year);

		if ($t2) {
			$t2->id_creator = $transaction->id_creator;
			$t2->save();
		}

		Utils::redirect('!acc/reports/journal.php?year=' . $year->id());
	}

	Utils::redirect('!acc/transactions/details.php?id=' . $transaction->id());
}, $csrf_key);


$previous_year = null;
$year_selected = f('from_year') !== null;
$chart_change = false;
$lines = [[]];
$years = Years::list(true);

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

if ($previous_year) {
	$lines = Reports::getAccountsBalances(['year' => $previous_year->id(), 'exclude_position' => [Account::EXPENSE, Account::REVENUE]]);

	if ($previous_year->id_chart != $year->id_chart) {
		$chart_change = true;
		$codes = [];

		foreach ($lines as $line) {
			$codes[] = $line->code;
		}

		$matching_accounts = $year->accounts()->listForCodes($codes);
	}

	// Append result
	$result = Reports::getResult(['year' => $previous_year->id()]);

	if ($result > 0) {
		$account = $year->accounts()->getSingleAccountForType(Account::TYPE_POSITIVE_RESULT);
	}
	else {
		$account = $year->accounts()->getSingleAccountForType(Account::TYPE_NEGATIVE_RESULT);
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
			if (array_key_exists($line->code, $matching_accounts)) {
				$acc = $matching_accounts[$line->code];
				$line->account = [$acc->id => sprintf('%s — %s', $acc->code, $acc->label)];
			}
		}
		else {
			$line->account = $line->id ? [$line->id => sprintf('%s — %s', $line->code, $line->label)] : null;
		}

		$line = (array) $line;
	}

	unset($line);
}

if (!empty($_POST['lines']) && is_array($_POST['lines'])) {
	$lines = Utils::array_transpose($_POST['lines']);

	foreach ($lines as &$line) {
		$line['credit'] = Utils::moneyToInteger($line['credit']);
		$line['debit'] = Utils::moneyToInteger($line['debit']);
	}
}

$tpl->assign(compact('lines', 'years', 'chart_change', 'previous_year', 'year_selected', 'year', 'csrf_key'));

$tpl->display('acc/years/balance.tpl');
