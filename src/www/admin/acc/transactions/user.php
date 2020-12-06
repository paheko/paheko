<?php
namespace Garradin;

use Garradin\Accounting\Reports;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$u = (new Membres)->get((int)qg('id'));

if (!$u) {
	throw new UserException('Ce membre n\'existe pas');
}

$years = Years::listAssoc();
end($years);
$year = (int)qg('year') ?: key($years);

$criterias = ['user' => $u->id];

$tpl->assign('balance', Reports::getClosingSumsWithAccounts($criterias + ['year' => $year]));
$tpl->assign('journal', Reports::getJournal($criterias));
$tpl->assign(compact('years', 'year'));
$tpl->assign('transaction_user', $u);

$tpl->display('acc/transactions/user.tpl');
