<?php
namespace Paheko;

use Paheko\Accounting\Reports;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$id = (int)qg('id');
$user = (int)qg('user');
$self_url = sprintf('!acc/transactions/subscription.php?id=%d&user=%d', $id, $user);

$form->runIf(qg('unlink') !== null, function () use ($id) {
	$t = Transactions::get((int)qg('unlink'));
	$t->deleteSubscriptionLink($id);
}, null, $self_url);

$criterias = ['subscription' => $id];
$action = ['shape' => 'delete', 'href' => $self_url . '&unlink=%d', 'label' => 'Dé-lier cette écriture'];

$tpl->assign('balance', Reports::getAccountsBalances($criterias));
$tpl->assign('journal', Reports::getJournal($criterias));
$tpl->assign('user_id', $user);
$tpl->assign('subscription_id', $id);
$tpl->assign(compact('action'));

$tpl->display('acc/transactions/subscription.tpl');
