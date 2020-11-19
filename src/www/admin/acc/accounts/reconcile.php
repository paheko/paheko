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

$start = null;
$end = null;

if (null !== qg('start') && null !== qg('end'))
{
	$start = \DateTime::createFromFormat('!d/m/Y', qg('start'));
	$end = \DateTime::createFromFormat('!d/m/Y', qg('end'));

	if (!$start || !$end) {
		$start = \DateTime::createFromFormat('!Y-m-d', qg('start'));
		$end = \DateTime::createFromFormat('!Y-m-d', qg('end'));
	}

	if (!$start || !$end) {
		$form->addError('La date donnée est invalide.');
	}
}

if (!$start || !$end) {
	$start = new \DateTime('first day of this month');
	$end = new \DateTime('last day of this month');
}

if ($start < $current_year->start_date || $start > $current_year->end_date
	|| $end < $current_year->start_date || $end > $current_year->end_date) {
	$start = clone $current_year->start_date;
	$end = clone $start;
	$end->modify('last day of this month');
}

$journal = $account->getReconcileJournal(CURRENT_YEAR_ID, $start, $end);

// Enregistrement des cases cochées
if ((f('save') || f('save_next')) && $form->check('acc_reconcile_' . $account->id))
{
	try {
		Transactions::saveReconciled($journal, f('reconcile'));

		if (f('save')) {
			Utils::redirect(Utils::getSelfURL());
		}
		else {
			$start->modify('+1 month');
			$end->modify('+1 month');
			$url = sprintf('%sacc/accounts/reconcile.php?id=%s&debut=%s&fin=%s&sauf=%s',
				ADMIN_URL, $account->id(), $start->format('Y-m-d'), $end->format('Y-m-d'), (int) qg('sauf'));
			Utils::redirect($url);
		}
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}

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
		'url' => sprintf($self_uri . '?id=%d&start=%s&end=%s&sauf=%d', $account->id, $prev->format('Y-m-01'), $prev->format('Y-m-t'), qg('sauf')),
	];
}

if (null !== $next) {
	$next = [
		'date' => $next,
		'url' => sprintf($self_uri . '?id=%d&start=%s&end=%s&sauf=%d', $account->id, $next->format('Y-m-01'), $next->format('Y-m-t'), qg('sauf')),
	];
}

$tpl->assign(compact(
	'account',
	'start',
	'end',
	'prev',
	'next',
	'journal'
));

$tpl->display('acc/accounts/reconcile.tpl');
