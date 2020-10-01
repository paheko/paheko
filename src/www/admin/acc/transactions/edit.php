<?php

namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
    throw new UserException('Cette écriture n\'existe pas');
}

$chart = $year->chart();
$accounts = $chart->accounts();


if (f('delete'))
{
    if ($form->check('acc_edit_' . $transaction->id))
    {
        try
        {
            $transaction->delete();
            Utils::redirect(ADMIN_URL . 'acc/');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('transaction', $transaction);

$lines = $transaction->getLinesWithAccounts();
$lines_accounts = [];

foreach ($lines as $k => $line) {
    $lines_accounts[$k] = [$line->id_account => sprintf('%s - %s', $line->account_code, $line->account_name)];
}

$tpl->assign('lines', $lines);
$tpl->assign('lines_accounts', $lines_accounts);
$tpl->assign('analytical_accounts', ['' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->assign('linked_users', $transaction->listLinkedUsersAssoc());

$tpl->display('acc/transactions/edit.tpl');
