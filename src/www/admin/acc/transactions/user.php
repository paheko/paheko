<?php
namespace Garradin;

use Garradin\Accounting\Reports;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$u = (new Membres)->get((int)qg('id'));

if (!$u) {
	throw new UserException('Ce membre n\'existe pas');
}

$criterias = ['user' => $u->id];

$tpl->assign('balance', Reports::getClosingSumsWithAccounts($criterias));
$tpl->assign('journal', Reports::getJournal($criterias));
$tpl->assign('transaction_user', $u);

$tpl->display('acc/transactions/user.tpl');
