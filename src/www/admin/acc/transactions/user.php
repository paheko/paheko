<?php
namespace Paheko;

use Paheko\Accounting\Reports;
use Paheko\Accounting\Years;
use Paheko\Users\Users;

require_once __DIR__ . '/../_inc.php';

$u = Users::get((int)qg('id'));

if (!$u) {
	throw new UserException('Ce membre n\'existe pas');
}

$years = Years::listAssoc();
end($years);
$year = (int)qg('year') ?: CURRENT_YEAR_ID;

$criterias = ['user' => $u->id];

$tpl->assign('balance', Reports::getAccountsBalances($criterias + ['year' => $year], null, false));
$tpl->assign('journal', Reports::getJournal($criterias, true));
$tpl->assign(compact('years', 'year'));
$tpl->assign('transaction_user', $u);

$tpl->display('acc/transactions/user.tpl');
