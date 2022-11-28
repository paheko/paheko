<?php
namespace Garradin;

use Garradin\Accounting\Reports;
use Garradin\Accounting\Years;
use Garradin\Users\Users;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$u = Users::get((int)qg('id'));

if (!$u) {
	throw new UserException('Ce membre n\'existe pas');
}

$years = Years::listAssoc();
end($years);
$year = (int)qg('year') ?: key($years);

$criterias = ['user' => $u->id];

$tpl->assign('balance', Reports::getAccountsBalances($criterias + ['year' => $year], null, false));
$tpl->assign('journal', Reports::getJournal($criterias));
$tpl->assign(compact('years', 'year'));
$tpl->assign('transaction_user', $u);

$tpl->display('acc/transactions/user.tpl');
