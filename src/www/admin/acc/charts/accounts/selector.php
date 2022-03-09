<?php

namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Accounting\Charts;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

$targets = explode(':', (string) qg('targets'));
$chart = (int) qg('chart') ?: null;

$targets = array_map('intval', $targets);
$targets_str = implode(':', $targets);

// Cache the page until the charts have changed
$last_change = Config::getInstance()->get('last_chart_change') ?: time();
$hash = sha1($targets_str . $chart . $last_change);

// Exit if there's no need to reload
Utils::HTTPCache($hash, null, 10);

if ($chart) {
	$chart = Charts::get($chart);
}
elseif (qg('year')) {
	$year = Years::get((int)qg('year'));

	if ($year) {
		$chart = $year->chart();
	}
}
elseif ($current_year) {
	$chart = $current_year->chart();
}

if (!$chart) {
	throw new UserException('Aucun exercice ouvert disponible');
}

$accounts = $chart->accounts();

$tpl->assign(compact('chart', 'targets', 'targets_str'));

$all = qg('all');

if (null !== $all) {
	$session->set('account_selector_all', (bool) $all);
}

$all = (bool) $session->get('account_selector_all');

if (!$targets) {
	$tpl->assign('accounts', !$all ? $accounts->listCommonTypes() : $accounts->listAll());
}
else {
	$tpl->assign('grouped_accounts', $accounts->listCommonGrouped($targets));
}

$tpl->assign('all', $all);

$tpl->display('acc/charts/accounts/selector.tpl');