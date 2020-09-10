<?php
namespace Garradin;

use Garradin\Entities\Accounting\Transaction;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ECRITURE);

$chart = $year->chart();
$accounts = $chart->accounts();

$transaction = new Transaction;

if (f('save')) {
    $transaction->import();
}

$tpl->assign('date', $session->get('context_compta_date') ?: false);
$tpl->assign('ok', (int) qg('ok'));

$tpl->assign('lines', $transaction->getLines() ?: [[]]);

$tpl->assign('analytical_accounts', ['label' => '-- Aucun'] + $accounts->listAnalytical());
$tpl->display('acc/transactions/new.tpl');
