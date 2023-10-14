<?php
namespace Paheko;

use Paheko\Accounting\Export;
use Paheko\Accounting\Import;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);
$user = $session->getUser();

$year_id = (int) qg('year') ?: CURRENT_YEAR_ID;

if ($year_id === CURRENT_YEAR_ID) {
	$year = $current_year;
}
else {
	$year = Years::get($year_id);
}

if (!$year) {
	throw new UserException("L'exercice demandé n'existe pas.");
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

$type = qg('type');
$type_name = Export::NAMES[$type] ?? null;
$csrf_key = 'acc_years_import_' . $year->id();
$examples = null;
$csv = new CSV_Custom($session, 'acc_import_year');
$ignore_ids = (bool) (f('ignore_ids') ?? qg('ignore_ids'));
$report = [];

$params = compact('ignore_ids', 'type') + ['year' => $year->id()];

if (f('cancel')) {
	$csv->clear();
	unset($params['type']);
	Utils::redirect(Utils::getSelfURI($params));
}

if ($type && $type_name) {
	$columns = Export::COLUMNS[$type];

	// Remove NULLs
	$columns = array_filter($columns);
	$columns_table = $columns = array_flip($columns);

	if ($type == Export::FEC) {
		// Fill with labels
		$columns_table = array_intersect_key(array_flip(Export::COLUMNS_FULL), $columns);
	}

	$csv->setColumns($columns_table, $columns);
	$csv->setMandatoryColumns(Export::MANDATORY_COLUMNS[$type]);

	$form->runIf(f('load') && isset($_FILES['file']['tmp_name']), function () use ($csv, $params) {
		$csv->load($_FILES['file']);
		Utils::redirect(Utils::getSelfURI($params));
	}, $csrf_key);

	$form->runIf(f('preview') && $csv->loaded(), function () use (&$csv) {
		$csv->skip((int)f('skip_first_line'));
		$csv->setTranslationTable(f('translation_table'));
	}, $csrf_key);

	if (!f('import') && $csv->ready()) {
		try {
			$report = Import::import($type, $year, $csv, $user->id, compact('ignore_ids') + ['dry_run' => true, 'return_report' => true]);
		}
		catch (UserException $e) {
			$csv->clear();
			$form->addError($e);
		}
	}

	$form->runIf(f('import') && $csv->loaded(), function () use ($type, &$csv, $year, $user, $ignore_ids) {
		try {
			Import::import($type, $year, $csv, $user->id, compact('ignore_ids'));
		}
		finally {
			$csv->clear();
		}
	}, $csrf_key, ADMIN_URL . 'acc/years/?msg=IMPORT');
}
else {
	$csv->clear();
	$examples = Export::getExamples($year);
}

$types = [
	Export::SIMPLE => [
		'label' => 'Simplifié (comptabilité de trésorerie)',
		'help' => 'Chaque ligne représente une écriture, comme dans un cahier. Les écritures avancées ne peuvent pas être importées dans ce format.',
	],
	Export::FULL => [
		'label' => 'Complet (comptabilité d\'engagement)',
		'help' => 'Permet d\'avoir des écritures avancées. Les écritures sont groupées en utilisant leur numéro.',
	],
	Export::GROUPED => [
		'label' => 'Complet groupé (comptabilité d\'engagement)',
		'help' => 'Permet d\'avoir des écritures avancées. Les 7 premières colonnes de chaque ligne sont vides pour indiquer les lignes suivantes de l\'écriture.',
	],
	Export::FEC => [
		'label' => 'FEC (Fichier des Écritures Comptables)',
		'help' => 'Format standard de l\'administration française.',
	],
];

$with_linked_users = ($table = $csv->getTranslationTable()) && in_array('linked_users', $table);

$tpl->assign(compact('csv', 'year', 'csrf_key', 'examples', 'type', 'type_name', 'ignore_ids', 'types', 'report', 'with_linked_users'));

$tpl->display('acc/years/import.tpl');
