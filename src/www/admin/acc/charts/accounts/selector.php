<?php

namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Accounting\Charts;

require_once __DIR__ . '/../../_inc.php';

header('X-Frame-Options: SAMEORIGIN', true);

if (!qg('chart') || !($chart = Charts::get((int)qg('chart')))) {
	throw new UserException('Aucun ID de plan comptable spécifié');
}

$accounts = $chart->accounts();

$tpl->assign(compact('chart'));

if (!qg('targets')) {
	$tpl->assign('accounts', $accounts->listAll());
}
else {
	$tpl->assign('grouped_accounts', $accounts->listCommonGrouped(explode(':', qg('targets'))));
}


$tpl->display('acc/charts/accounts/selector.tpl');