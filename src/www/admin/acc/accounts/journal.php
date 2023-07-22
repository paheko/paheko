<?php
namespace Paheko;

use Paheko\Accounting\Accounts;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$account = Accounts::get((int) qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

$year_id = (int) qg('year') ?: CURRENT_YEAR_ID;

if (!$year_id) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

if ($year_id === CURRENT_YEAR_ID) {
	$year = $current_year;
}
else {
	$year = Years::get($year_id);

	if (!$year) {
		throw new UserException("L'exercice demandé n'existe pas.");
	}

	$tpl->assign('year', $year);
}

// The account has a different chart after changing the current year:
// get back to the list of accounts to select a new account!
if ($account->id_chart != $year->id_chart) {
	Utils::redirect(ADMIN_URL . 'acc/accounts/?chart_change');
}

// The account has a different chart after changing the current year:
// get back to the list of accounts to select a new account!
if ($account->id_chart != $year->id_chart) {
	Utils::redirect(ADMIN_URL . 'acc/accounts/?chart_change');
}

$can_edit = $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN) && !$year->closed;
$simple = !empty($session->user()->preferences->accounting_expert) ? false : true;

// Use simplified view for favourite accounts
if (null === $simple) {
	$simple = (bool) $account->type;
}

$filter = new \stdClass;
$filter->start = Utils::get_datetime(qg('start'));
$filter->end = Utils::get_datetime(qg('end'));

$list = $account->listJournal($year_id, $simple, $filter->start, $filter->end);
$list->setTitle(sprintf('Journal - %s - %s', $account->code, $account->label));
$list->loadFromQueryString();

$sum = null;

if (!$filter->start && !$filter->end) {
	$sum = $account->getSum($year_id, $simple);
}

$tpl->assign(compact('simple', 'year', 'account', 'list', 'sum', 'can_edit', 'filter'));

$tpl->display('acc/accounts/journal.tpl');
