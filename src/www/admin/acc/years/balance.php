<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Accounting\Reports;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

if (f('next') && !f('from_year')) {
	Utils::redirect('/admin/acc/years/');
}

if (f('save') && $form->check('acc_years_balance_' . $year->id()))
{
	try {
		$transaction = new Transaction;
		$transaction->importFromBalanceForm($year);
		$transaction->save();

		Utils::redirect(ADMIN_URL . 'acc/transactions/details.php?id=' . $transaction->id());
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$previous_year = null;
$chart_change = false;
$lines = [[]];
$lines_accounts = [[]];
$years = Years::listClosed();

if (!count($years)) {
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
	$lines = Reports::getClosingSumsWithAccounts(['year' => $previous_year->id(), 'exclude_position' => [Account::EXPENSE, Account::REVENUE]]);

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
			'label' => 'Résultat de l\'exercice',
		];
	}

	$lines[] = (object) [
		'sum' => $result,
		'id' => $account->id,
		'code' => $account->code,
		'label' => $account->label,
	];

	foreach ($lines as $k => &$line) {
		$line->credit = $line->sum > 0 ? $line->sum : 0;
		$line->debit = $line->sum < 0 ? abs($line->sum) : 0;

		if ($chart_change) {
			if (array_key_exists($line->code, $matching_accounts)) {
				$acc = $matching_accounts[$line->code];
				$line->account_selected = [$acc->id => sprintf('%s — %s', $acc->code, $acc->label)];
			}
			else {
				$line->account_selected = null;
			}
		}
		else {
			$line->account_selected = [$line->id => sprintf('%s — %s', $line->code, $line->label)];
		}
	}

	unset($line);
}

$tpl->assign(compact('lines', 'years', 'chart_change', 'previous_year', 'year'));

$tpl->display('acc/years/balance.tpl');
