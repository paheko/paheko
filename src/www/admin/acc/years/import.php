<?php
namespace Garradin;

use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$year_id = (int) qg('id') ?: CURRENT_YEAR_ID;

if ($year_id === CURRENT_YEAR_ID) {
	$year = $current_year;
}
else {
	$year = Years::get($year_id);

	if (!$year) {
		throw new UserException("L'exercice demandé n'existe pas.");
	}
}

if (qg('export')) {
	CSV::export(
		qg('export'),
		sprintf('Export comptable - %s - %s', Config::getInstance()->get('nom_asso'), $year->label),
		Transactions::export($year->id())
	);
	exit;
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

$csv = new CSV_Custom($session, 'acc_import_year');
$csv->setColumns(Transactions::POSSIBLE_CSV_COLUMNS);
$csv->setMandatoryColumns(Transactions::MANDATORY_CSV_COLUMNS);

if (f('cancel')) {
	$csv->clear();
	Utils::redirect(Utils::getSelfURL());
}

$csrf_key = 'acc_years_import_' . $year->id();

$form->runIf(f('assign') && $csv->loaded(), function () use ($csv, $year, $user) {
	$csv->skip((int)f('skip_first_line'));
	$csv->setTranslationTable(f('translation_table'));

	Transactions::importCustom($year, $csv, $user->id);
	$csv->clear();
}, $csrf_key, ADMIN_URL . 'acc/years/');

$form->runIf('load', function () use ($csv, $year, $user) {
	if (f('type') == 'garradin') {
		Transactions::importCSV($year, $_FILES['file'], $user->id);
		Utils::redirect(ADMIN_URL . 'acc/years/');
	}
	elseif (isset($_FILES['file']['tmp_name'])) {
		$csv->load($_FILES['file']);
		Utils::redirect(Utils::getSelfURI());
	}
	else {
		throw new UserException('Fichier invalide');
	}
}, $csrf_key, Utils::getSelfURI());

$tpl->assign(compact('csv', 'year', 'csrf_key'));

$tpl->display('acc/years/import.tpl');
