<?php
namespace Garradin;

use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../_inc.php';

$account = Accounts::get((int) qg('id'));

if (!$account) {
    throw new UserException("Le compte demandé n'existe pas.");
}

$journal = $account->getJournal(CURRENT_YEAR_ID);
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

$tpl->assign('account', $account);
$tpl->assign('journal', $journal);
$tpl->assign('sum', $sum);
$tpl->display('acc/accounts/journal.tpl');
