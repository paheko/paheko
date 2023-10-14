<?php

namespace Paheko;

use Paheko\Entities\Accounting\Account;
use Paheko\Accounting\Charts;
use Paheko\Accounting\Years;

const ALLOW_ACCOUNTS_ACCESS = true;

require_once __DIR__ . '/../../_inc.php';

$targets = qg('targets');
$targets = $targets ? explode(':', $targets) : [];
$chart_id = (int) qg('chart') ?: null;
$year_id = (int)qg('year') ?: null;

$targets = array_map('intval', $targets);
$targets_str = implode(':', $targets);

$year = null;
$filter = qg('filter');

$this_url = '?' . http_build_query([
	'targets' => $targets_str,
	'chart' => $chart_id,
	'year' => $year_id,
]);

if (qg('_dialog') !== null) {
	$this_url .= '&_dialog';
}

if (!count($targets)) {
	$targets = null;
}

if (null !== $filter) {
	$session->set('account_selector_filter', $filter);
	$session->save();
}

$filter = $session->get('account_selector_filter') ?? 'usual';


// Cache the page until the charts have changed
$last_change = Config::getInstance()->get('last_chart_change') ?: time();
$hash = sha1($targets_str . $chart_id . $year_id . $last_change . '=' . $filter);

// Exit if there's no need to reload
Utils::HTTPCache($hash, null, 10);

$chart = null;

if ($chart_id) {
	$chart = Charts::get($chart_id);
}
elseif ($year_id) {
	$year = Years::get($year_id);

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

// Charts with no country don't allow to use types
if (!$chart->country) {
	$targets = null;
}

$accounts = $chart->accounts();

$edit_url = sprintf('!acc/charts/accounts/%s?id=%d&types=%s', isset($grouped_accounts) ? '' : 'all.php', $chart->id(), $targets_str);
$new_url = sprintf('!acc/charts/accounts/new.php?id=%d&types=%s', $chart->id(), $targets_str);

$targets_names = !empty($targets) ? array_intersect_key(Account::TYPES_NAMES, array_flip($targets)) : [];
$targets_names = implode(', ', $targets_names);

$tpl->assign(compact('chart', 'targets', 'targets_str', 'filter', 'new_url', 'edit_url', 'targets_names', 'this_url'));

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