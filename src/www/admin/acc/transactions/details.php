<?php
namespace Garradin;

use Garradin\Entities\Accounting\Transaction;
use KD2\DB\EntityManager as EM;

require_once __DIR__ . '/../_inc.php';

$transaction = EM::findOneById(Transaction::class, (int) qg('id'));

if (!$transaction) {
    throw new UserException('Cette écriture n\'existe pas');
}

$tpl->assign('files', Fichiers::listLinkedFiles(Fichiers::LIEN_COMPTA, $transaction->id()));
$tpl->assign('transaction', $transaction);
$tpl->assign('tr_year', $transaction->year());

$tpl->display('acc/transactions/details.tpl');
