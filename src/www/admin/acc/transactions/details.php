<?php
namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
	throw new UserException('Cette écriture n\'existe pas');
}

$csrf_key = 'details_' . $transaction->id();

$form->runIf('mark_paid', function () use ($transaction) {
	$transaction->markPaid();
	$transaction->save();
}, $csrf_key, Utils::getSelfURL());

$tpl->assign(compact('transaction', 'csrf_key'));

$tpl->assign('files', $transaction->listFiles());
$tpl->assign('tr_year', $transaction->year());
$tpl->assign('creator_name', $transaction->id_creator ? (new Membres)->getNom($transaction->id_creator) : null);

$tpl->assign('related_users', $transaction->listLinkedUsers());

$tpl->display('acc/transactions/details.tpl');
