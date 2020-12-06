<?php
namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
	throw new UserException('Cette écriture n\'existe pas');
}

$tpl->assign('files', $transaction->listFiles());
$tpl->assign('transaction', $transaction);
$tpl->assign('tr_year', $transaction->year());
$tpl->assign('creator_name', $transaction->id_creator ? (new Membres)->getNom($transaction->id_creator) : null);

$tpl->assign('related_users', $transaction->listLinkedUsers());

$tpl->display('acc/transactions/details.tpl');
