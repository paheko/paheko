<?php

use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/../_inc.php';

if (empty($_GET['target'])) {
	throw new Garradin\UserException('Aucune cible spécifiée');
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
		break;
}

$chart = $current_year->chart();
$accounts = $chart->accounts();

if ($_GET['target'] == 'all') {
	$tpl->assign('accounts', $accounts->listAll());
}
else {
	$tpl->assign('grouped_accounts', $accounts->listCommonGrouped($types));
}


$tpl->display('acc/accounts/selector.tpl');