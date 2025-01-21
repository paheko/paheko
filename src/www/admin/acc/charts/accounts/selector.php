<?php

namespace Paheko;

use Paheko\Entities\Accounting\Account;
use Paheko\Accounting\Charts;
use Paheko\Accounting\Years;

const ALLOW_ACCOUNTS_ACCESS = true;

require_once __DIR__ . '/../../_inc.php';

// List accounts by types
$types = $_GET['types'] ?? '';
$types = explode('|', (string) $types);
$types = array_map('intval', $types);
$types = array_filter($types);

// List accounts by codes
$codes = $_GET['codes'] ?? '';
$codes = explode('|', (string) $codes);
$codes = array_filter($codes);

// List only for given chart or given year
$id_chart = intval($_GET['id_chart'] ?? 0);
$id_year = intval($_GET['id_year'] ?? 0);

if ($id_chart && $id_year) {
	throw new UserException('Invalid call: id_chart and id_year cannot be specified at the same time', 400);
}

$key = ($_GET['key'] ?? null) === 'code' ? 'code' : 'id';

$saved_filter = $session->get('account_selector_filter');
$filter = $_GET['filter'] ?? $saved_filter;
$filter = in_array($filter, ['all', 'no_bookmarks', 'bookmarks'], true) ? $filter : 'bookmarks';

// Save filter in session if it did change
if ($saved_filter !== $filter && $filter !== 'no_bookmarks') {
	$session->set('account_selector_filter', $filter);
	$session->save();
}

// Create self URL
$filter_all_url = Utils::getModifiedURL('?filter=all');
$filter_bookmarks_url = Utils::getModifiedURL('?filter=bookmarks');

// Cache the page until the charts have changed
$last_change = Config::getInstance()->get('last_chart_change') ?: time();
$params = $_GET;
$params['filter'] = $filter;
$hash = sha1(http_build_query($params));

// This method will exit here if the list has already been cached by the client
Utils::HTTPCache($hash, null, 1);

// Find the chart we need to use
$chart = null;

if ($id_chart) {
	$chart = Charts::get($id_chart);
}
elseif ($id_year) {
	$year = Years::get($id_year);

	if ($year) {
		$chart = $year->chart();
	}
}
elseif ($current_year) {
	$chart = $current_year->chart();
}

if (!$chart) {
	throw new UserException('Aucun exercice n\'est ouvert.');
}

// Charts with no country don't allow to use types,
// so we can't filter by type here
if (!$chart->country) {
	$types = [];
}

$accounts = $chart->accounts();

$chart_params = http_build_query([
	'id' => $chart->id(),
	'types' => $_GET['types'] ?? '',
]);

$edit_url = sprintf('!acc/charts/accounts/%s?%s', $filter !== 'bookmarks' ? 'all.php' : '', $chart_params);
$edit_url = Utils::getLocalURL($edit_url);
$new_url = sprintf('!acc/charts/accounts/new.php?%s', $chart_params);
$new_url = Utils::getLocalURL($new_url);

$types_names = !empty($types) ? array_intersect_key(Account::TYPES_NAMES, array_flip($types)) : [];
$types_names = implode(', ', $types_names);

$criterias = compact('types', 'codes');
$criterias = array_filter($criterias);
$grouped_accounts = $all_accounts = null;

if ($filter === 'bookmarks') {
	$grouped_accounts = $accounts->listCommonGrouped($criterias);
}
else {
	$all_accounts = $accounts->listAll($criterias);
}

$tpl->assign(compact(
	'chart',
	'types',
	'codes',
	'filter',
	'new_url',
	'edit_url',
	'types_names',
	'filter_bookmarks_url',
	'filter_all_url',
	'key',
	'grouped_accounts',
	'all_accounts'
));

$tpl->register_modifier('make_label_searchable', function ($account, ...$keys) {
	$account = $account instanceof Account ? $account->asArray() : (array)$account;
	$txt = implode(' ', array_intersect_key($account, array_flip($keys)));
	$txt = strtolower(Utils::transliterateToAscii($txt));
	return $txt;
});

$tpl->display('acc/charts/accounts/selector.tpl');
