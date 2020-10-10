<?php
namespace Garradin;

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
$lines = [[]];
$lines_accounts = [[]];
$years = Years::listClosed();

if (!count($years)) {
	$previous_year = 0;
}
elseif (null !== f('from_year')) {
	$previous_year = (int)f('from_year');
}

if ($previous_year) {
	$lines = Reports::getClosingSumsWithAccounts(['year' => $previous_year]);
	$lines_accounts = [];

	foreach ($lines as $k => $line) {
		$lines_accounts[$k] = [$line->id => sprintf('%s — %s', $line->code, $line->label)];
	}
}

$tpl->assign('lines', $lines);
$tpl->assign('lines_accounts', $lines_accounts);
$tpl->assign('years', $years);
$tpl->assign('previous_year', $previous_year);
$tpl->assign('year', $year);

$tpl->display('acc/years/balance.tpl');
