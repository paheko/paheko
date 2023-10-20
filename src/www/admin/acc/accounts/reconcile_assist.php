<?php
namespace Paheko;

use Paheko\Accounting\Accounts;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\AssistedReconciliation;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

if (!CURRENT_YEAR_ID) {
	Utils::redirect(ADMIN_URL . 'acc/years/?msg=OPEN');
}

$account = Accounts::get((int)qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

$csrf_key = 'acc_reconcile_assist_' . $account->id();

$ar = new AssistedReconciliation($account);
$csv = $ar->csv();

$form->runIf('cancel', function () use ($csv) {
	$csv->clear();
}, $csrf_key, Utils::getSelfURI());

$form->runIf(f('upload') && isset($_FILES['file']['name']), function () use ($csv) {
	$csv->load($_FILES['file']);
}, $csrf_key, Utils::getSelfURI());

$form->runIf('assign', function () use ($ar) {
	$ar->setSettings(f('translation_table'), (int)f('skip_first_line'));
}, $csrf_key, Utils::getSelfURI());

$start = $end = null;

if (null !== qg('start') && null !== qg('end')) {
	$start = \DateTime::createFromFormat('!d/m/Y', qg('start'));
	$end = \DateTime::createFromFormat('!d/m/Y', qg('end'));

	if (!$start || !$end) {
		$form->addError('La date donnée est invalide.');
	}
}
else {
	try {
		extract($ar->getStartAndEndDates());
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
		$csv->clear();
	}
}

$journal = null;

if ($start && $end) {
	if ($start < $current_year->start_date || $start > $current_year->end_date) {
		$start = clone $current_year->start_date;
	}

	if ($end < $current_year->start_date || $end > $current_year->end_date) {
		$end = clone $current_year->end_date;
	}

	$journal = $account->getReconcileJournal(CURRENT_YEAR_ID, $start, $end);
}

// Enregistrement des cases cochées
$form->runIf('save', function () use ($journal, $csv) {
	Transactions::saveReconciled($journal, f('reconcile'));
	$csv->clear();
}, $csrf_key, Utils::getSelfURI());

$lines = null;

if ($journal && $csv->ready()) {
	try {
		$lines = $ar->mergeJournal($journal, $start, $end);
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}

$tpl->assign(compact(
	'account',
	'start',
	'end',
	'lines',
	'ar',
	'csv',
	'csrf_key'
));

$tpl->display('acc/accounts/reconcile_assist.tpl');
