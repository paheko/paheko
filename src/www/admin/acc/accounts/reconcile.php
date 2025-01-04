<?php
namespace Paheko;

use Paheko\Accounting\Accounts;
use Paheko\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$account = Accounts::get((int)qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

// The account has a different chart after changing the current year:
// get back to the list of accounts to select a new account!
if ($account->id_chart != $current_year->id_chart) {
	Utils::redirect(ADMIN_URL . 'acc/accounts/?chart_change');
}

$start = new \DateTime('first day of this month');
$end = new \DateTime('last day of this month');
$filter = (int)qg('filter') ?: $account::RECONCILE_ALL;
$desc = (bool) qg('desc');
$sum_start = Utils::moneyToInteger(qg('sum_start'));
$sum_end = Utils::moneyToInteger(qg('sum_end'));
$has_advanced_options = $filter || $sum_start || $sum_end || $desc;

$sum_start_diff = null;
$sum_end_diff = null;

$desc_options = [
	0 => 'Chronologique',
	1 => 'Du plus récent au plus ancien',
];

$filter_options = [
	$account::RECONCILE_ALL => 'Toutes les écritures',
	$account::RECONCILE_ONLY => 'Seulement les écritures rapprochées',
	$account::RECONCILE_MISSING => 'Seulement les écritures non rapprochées',
];

if (null !== qg('start') && null !== qg('end'))
{
	$start = \DateTime::createFromFormat('!d/m/Y', qg('start'));
	$end = \DateTime::createFromFormat('!d/m/Y', qg('end'));

	if (!$start || !$end) {
		$form->addError('La date donnée est invalide.');
	}
}

if ($start < $current_year->start_date || $start > $current_year->end_date) {
	$start = clone $current_year->start_date;
}

if ($end < $current_year->start_date || $end > $current_year->end_date) {
	$end = clone $current_year->end_date;
}

if ($start > $end) {
	$end = clone $start;
}

$journal = $account->getReconcileJournal($current_year->id(), $start, $end, $filter, $desc);

$has_unreconciled = $account->hasUnreconciledLinesBefore($current_year->id(), $start);

if ($sum_end) {
	$sum_after = $account->getSumAtDate($current_year->id(), (clone $end)->modify('+1 day'), true);
	$sum_after *= -1;
	$sum_end_diff = $sum_after - $sum_end;
}

// Enregistrement des cases cochées
$form->runIf(f('save') || f('save_next'), function () use ($journal, $start, $account, $filter, $desc) {
	Transactions::saveReconciled($journal, f('reconcile'));

	if (f('save')) {
		Utils::redirect(Utils::getSelfURI());
	}
	else {
		$start->modify('+1 month');
		$url = sprintf('%sacc/accounts/reconcile.php?id=%s&start=%s&end=%s&filter=%d&desc=%d',
			ADMIN_URL, $account->id(), $start->format('01/m/Y'), $start->format('t/m/Y'), $filter, $desc);
		Utils::redirect($url);
	}
}, 'acc_reconcile_' . $account->id());

$prev = clone $start;
$next = clone $start;
$prev->modify('-1 month');
$next->modify('+1 month');

if ($next > $current_year->end_date) {
	$next = $current_year->end_date;
}

if ($prev < $current_year->start_date) {
	$prev = $current_year->start_date;
}

if ($start == $current_year->start_date) {
	$prev = null;
}
elseif ($end == $current_year->end_date) {
	$next = null;
}

$self_uri = Utils::getSelfURI(false);

if (null !== $prev) {
	$prev = [
		'date' => $prev,
		'url' => sprintf($self_uri . '?id=%d&start=%s&end=%s&filter=%d&desc=%d', $account->id, $prev->format('01/m/Y'), $prev->format('t/m/Y'), $filter, $desc),
	];
}

if (null !== $next) {
	$next = [
		'date' => $next,
		'url' => sprintf($self_uri . '?id=%d&start=%s&end=%s&filter=%d&desc=%d', $account->id, $next->format('01/m/Y'), $next->format('t/m/Y'), $filter, $desc),
	];
}

$tpl->assign(compact(
	'account',
	'start',
	'end',
	'prev',
	'next',
	'journal',
	'filter',
	'filter_options',
	'sum_start',
	'sum_start_diff',
	'sum_end',
	'sum_end_diff',
	'has_unreconciled',
	'has_advanced_options',
	'desc',
	'desc_options'
));

$tpl->display('acc/accounts/reconcile.tpl');
