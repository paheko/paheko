<?php

namespace Garradin;

use Garradin\Accounting\Reports;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/_inc.php';

$liability = Reports::getClosingSumsWithAccounts($criterias + ['position' => Account::LIABILITY]);
$asset = Reports::getClosingSumsWithAccounts($criterias + ['position' => Account::ASSET]);

$result = Reports::getResult($criterias);

if ($result > 0) {
	$result = (object) ['id' => null, 'label' => 'Résultat de l\'exercice courant (excédent)', 'sum' => $result];
	$liability = array_merge($liability, [$result]);
}
elseif ($result < 0) {
	$result = (object) ['id' => null, 'label' => 'Résultat de l\'exercice courant (débiteur)', 'sum' => abs($result)];
	$asset = array_merge($asset, [$result]);
}

$get_sum = function (array $in): int {
	$sum = 0;

	foreach ($in as $row) {
		$sum += $row->sum;
	}

	return abs($sum);
};

$liability_sum = $get_sum($liability);
$asset_sum = $get_sum($asset);

$tpl->assign(compact('liability', 'asset', 'liability_sum', 'asset_sum'));

$tpl->display('acc/reports/balance_sheet.tpl');
