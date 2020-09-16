<?php
namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
    throw new UserException('Cette écriture n\'existe pas');
}

$tpl->assign('files', Fichiers::listLinkedFiles(Fichiers::LIEN_COMPTA, $transaction->id()));
$tpl->assign('transaction', $transaction);
$tpl->assign('tr_year', $transaction->year());

$tpl->display('acc/transactions/details.tpl');
