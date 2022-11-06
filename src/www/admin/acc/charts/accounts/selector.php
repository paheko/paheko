<?php

namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Accounting\Charts;
use Garradin\Accounting\Years;

const ALLOW_ACCOUNTS_ACCESS = true;

require_once __DIR__ . '/../../_inc.php';

$targets = qg('targets');
$targets = $targets ? explode(':', $targets) : [];
$chart = (int) qg('chart') ?: null;

$targets = array_map('intval', $targets);
$targets_str = implode(':', $targets);

$year = null;
$filter = qg('filter');
$filter_options = [
//	'bookmark' => 'Voir seulement les comptes favoris',
	'usual' => 'Voir seulement les comptes favoris et usuels',
	'all' => 'Voir tous les comptes',
];

if (!count($targets)) {
	$filter_options['all'] = 'Voir tout le plan comptable';
	$targets = null;
}

if (null !== $filter) {
	if (!array_key_exists($filter, $filter_options)) {
		$filter = 'usual';
	}

	$session->set('account_selector_filter', $filter);
}

$filter = $session->get('account_selector_filter') ?? 'usual';


// Cache the page until the charts have changed
$last_change = Config::getInstance()->get('last_chart_change') ?: time();
$hash = sha1($targets_str . $chart . $last_change . '=' . $filter);

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
	$year = $current_year;
}

if (!$chart) {
	throw new UserException('Aucun exercice ouvert disponible');
}

$accounts = $chart->accounts();

$edit_url = sprintf('!acc/charts/accounts/%s?id=%d&types=%s', isset($grouped_accounts) ? '' : 'all.php', $chart->id(), $targets_str);

$tpl->assign(compact('chart', 'targets', 'targets_str', 'filter_options', 'filter', 'edit_url'));

if ($filter == 'all') {
	$tpl->assign('accounts', $accounts->listAll($targets));
}
elseif ($year) {
	$tpl->assign('grouped_accounts', $year->listCommonAccountsGrouped($targets));
}
else {
	$tpl->assign('grouped_accounts', $accounts->listCommonGrouped($targets));
}

$tpl->display('acc/charts/accounts/selector.tpl');