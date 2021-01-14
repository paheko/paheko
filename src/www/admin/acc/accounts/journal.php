<?php
namespace Garradin;

use Garradin\Accounting\Accounts;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$account = Accounts::get((int) qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

$year_id = (int) qg('year') ?: CURRENT_YEAR_ID;

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
if ($account->id_chart != $current_year->id_chart) {
	Utils::redirect(ADMIN_URL . 'acc/accounts/?chart_change');
}

$can_edit = $session->canAccess('compta', Membres::DROIT_ADMIN) && !$year->closed;
$simple = qg('simple');

// Use simplified view for favourite accounts
if (null === $simple) {
	$simple = (bool) $account->type;
}

$list = $account->listJournal($year_id, $simple);
$list->setTitle(sprintf('Journal - %s - %s', $account->code, $account->label));
$list->loadFromQueryString();

$sum = $account->getSum($year_id, $simple);
$tpl->assign(compact('simple', 'year', 'account', 'list', 'sum', 'can_edit'));

$tpl->display('acc/accounts/journal.tpl');
