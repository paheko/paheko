<?php
namespace Garradin;

use Garradin\Accounting\Accounts;
use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

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
$only = (bool) qg('only');

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

$journal = $account->getReconcileJournal($current_year->id(), $start, $end, $only);

// Enregistrement des cases cochées
$form->runIf(f('save') || f('save_next'), function () use ($journal, $start, $end, $account, $only) {
	Transactions::saveReconciled($journal, f('reconcile'));

	if (f('save')) {
		Utils::redirect(Utils::getSelfURL());
	}
	else {
		$start->modify('+1 month');
		$end->modify('+1 month');
		$url = sprintf('%sacc/accounts/reconcile.php?id=%s&start=%s&end=%s&only=%d',
			ADMIN_URL, $account->id(), $start->format('d/m/Y'), $end->format('d/m/Y'), $only);
		Utils::redirect($url);
	}
}, 'acc_reconcile_' . $account->id());

$prev = clone $start;
$next = clone $start;
$prev->modify('-1 month');
$next->modify('+1 month');

if ($next > $current_year->end_date) {
	$next = null;
}

if ($prev < $current_year->start_date) {
	$prev = null;
}

$self_uri = Utils::getSelfURI(false);

if (null !== $prev) {
	$prev = [
		'date' => $prev,
		'url' => sprintf($self_uri . '?id=%d&start=%s&end=%s&only=%d', $account->id, $prev->format('01/m/Y'), $prev->format('t/m/Y'), $only),
	];
}

if (null !== $next) {
	$next = [
		'date' => $next,
		'url' => sprintf($self_uri . '?id=%d&start=%s&end=%s&only=%d', $account->id, $next->format('01/m/Y'), $next->format('t/m/Y'), $only),
	];
}

$tpl->assign(compact(
	'account',
	'start',
	'end',
	'prev',
	'next',
	'journal',
	'only'
));

$tpl->display('acc/accounts/reconcile.tpl');
