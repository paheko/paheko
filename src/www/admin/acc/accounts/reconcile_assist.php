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

$csrf_key = 'acc_reconcile_assist_' . $account->id();
$csv = new CSV_Custom($session, 'acc_reconcile_csv');

$csv->setColumns([
	'label'          => 'Libellé',
	'date'           => 'Date',
	'notes'          => 'Remarques',
	'reference'      => 'Numéro pièce comptable',
	'p_reference'    => 'Référence paiement',
	'amount'         => 'Montant',
]);

$csv->setMandatoryColumns(['label', 'date', 'amount']);

$form->runIf('cancel', function () use ($csv) {
	$csv->clear();
}, $csrf_key, Utils::getSelfURL());

$form->runIf(f('upload') && isset($_FILES['file']['name']), function () use ($csv) {
	$csv->load($_FILES['file']);
}, $csrf_key, Utils::getSelfURL());

$form->runIf('assign', function () use ($csv) {
	$csv->setTranslationTable(f('translation_table'));
	$csv->skip((int)f('skip_first_line'));
}, $csrf_key, Utils::getSelfURL());

$start = null;
$end = null;
$journal = null;

if ($csv->ready()) {
	foreach ($csv->iterate() as $line => $row) {
		$date = \DateTime::createFromFormat('!d/m/Y', $row->date);
		if (!$date) {
			$form->addError(sprintf('Ligne %d : format de date invalide (%s)', $line, $row->date));
			continue;
		}

		if ($date < $start) {
			$start = $date;
		}

		if ($date > $end) {
			$end = $date;
		}
	}

	if ($start < $current_year->start_date || $start > $current_year->end_date) {
		$start = clone $current_year->start_date;
	}

	if ($end < $current_year->start_date || $end > $current_year->end_date) {
		$end = clone $current_year->end_date;
	}
}

if ($start && $end) {
	$journal = $account->getReconcileJournal(CURRENT_YEAR_ID, $start, $end);
}

// Enregistrement des cases cochées
$form->runIf('save', function () use ($journal, $csv) {
	Transactions::saveReconciled($journal, f('reconcile'));
	$csv->clear();
}, $csrf_key, Utils::getSelfURL());

$lines = null;

if ($journal && $csv->ready()) {
	try {
		$lines = $account->mergeReconcileJournalAndCSV($journal, $csv);
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
	'csv',
	'csrf_key'
));

$tpl->display('acc/accounts/reconcile_assist.tpl');
