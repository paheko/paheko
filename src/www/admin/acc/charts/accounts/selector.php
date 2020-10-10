<?php

namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Accounting\Charts;

require_once __DIR__ . '/../../_inc.php';

if (!qg('target')) {
	throw new UserException('Aucune cible spécifiée');
}

if (!qg('chart') || !($chart = Charts::get((int)qg('chart')))) {
	throw new UserException('Aucun ID de plan comptable spécifié');
}

switch ($_GET['target']) {
	case 'common':
		$types = [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING];
		break;
	case 'expense':
		$types = [Account::TYPE_EXPENSE];
		break;
	case 'revenue':
		$types = [Account::TYPE_REVENUE];
		break;
	case 'thirdparty':
		$types = [Account::TYPE_THIRD_PARTY];
		break;
	default:
		throw new UserException('Invalid target');
}

$accounts = $chart->accounts();

if ($_GET['target'] == 'all') {
	$tpl->assign('accounts', $accounts->listAll());
}
else {
	$tpl->assign('grouped_accounts', $accounts->listCommonGrouped($types));
}


$tpl->display('acc/charts/accounts/selector.tpl');