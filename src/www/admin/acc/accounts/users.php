<?php
namespace Paheko;

use Paheko\Accounting\Accounts;

require_once __DIR__ . '/../_inc.php';

if (!CURRENT_YEAR_ID) {
	throw new UserException('Aucun exercice sélectionné');
}

$accounts = new Accounts($current_year->id_chart);

$all = qg('all') !== '0';

$list = $accounts->listUserAccounts($current_year->id, !$all);
$list->loadFromQueryString();

$tpl->assign('chart_id', $current_year->id_chart);

$tpl->assign(compact('list', 'all'));

$tpl->display('acc/accounts/users.tpl');
