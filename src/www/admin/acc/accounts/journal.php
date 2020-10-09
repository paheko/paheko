<?php
namespace Garradin;

use Garradin\Accounting\Accounts;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$account = Accounts::get((int) qg('id'));

if (!$account) {
    throw new UserException("Le compte demandé n'existe pas.");
}

$year_id = (int) qg('year') ?: CURRENT_YEAR_ID;

if ($year_id !== CURRENT_YEAR_ID) {
	$year = Years::get($year_id);

	if (!$year) {
		throw new UserException("L'exercice demandé n'existe pas.");
	}

	$tpl->assign('year', $year);
}

$journal = $account->getJournal($year_id);
$sum = 0;

if (count($journal)) {
	$sum = end($journal)->running_sum;
}

/*
if (($compte->position & Compta\Comptes::ACTIF) || ($compte->position & Compta\Comptes::CHARGE))
{
    $tpl->assign('credit', '-');
    $tpl->assign('debit', '+');
}
else
{
    $tpl->assign('credit', '+');
    $tpl->assign('debit', '-');
}
*/

$tpl->assign('simple_view', qg('simple'));
$tpl->assign('year_id', $year_id);
$tpl->assign('account', $account);
$tpl->assign('journal', $journal);
$tpl->assign('sum', $sum);
$tpl->display('acc/accounts/journal.tpl');
