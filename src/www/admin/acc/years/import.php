<?php
namespace Garradin;

use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

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

if (qg('export')) {
	Transactions::export($year->id());
	exit;
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

$type = qg('type');
$type_name = Transactions::EXPORT_NAMES[$type] ?? null;
$csrf_key = 'acc_years_import_' . $year->id();
$examples = null;
$csv = new CSV_Custom($session, 'acc_import_year');
$ignore_ids = (bool) (f('ignore_ids') ?? qg('ignore_ids'));

$params = ['year' => $year->id(), 'ignore_ids' => (int) $ignore_ids, 'type' => $type];

if (f('cancel')) {
	$csv->clear();
	unset($params['type']);
	Utils::redirect(Utils::getSelfURI($params));
}

if ($type && $type_name) {
	$columns = Transactions::EXPORT_COLUMNS[$type];
	unset($columns['linked_users']);
	$csv->setColumns($columns);
	$csv->setMandatoryColumns(Transactions::MANDATORY_COLUMNS[$type]);

	$form->runIf(f('assign') && $csv->loaded(), function () use ($type, $csv, $year, $user, $ignore_ids) {
		$csv->skip((int)f('skip_first_line'));
		$csv->setTranslationTable(f('translation_table'));

		Transactions::import($type, $year, $csv, $user->id, (bool) $ignore_ids);
		$csv->clear();
	}, $csrf_key, ADMIN_URL . 'acc/years/?msg=IMPORT');

	$form->runIf(f('load') && isset($_FILES['file']['tmp_name']), function () use ($type, $csv, $year, $params) {
		$csv->load($_FILES['file']);
		Utils::redirect(Utils::getSelfURI($params));
	}, $csrf_key);
}
else {
	$csv->clear();
	$examples = Transactions::getExportExamples($year);
}

$tpl->assign(compact('csv', 'year', 'csrf_key', 'examples', 'type', 'type_name', 'ignore_ids'));

$tpl->display('acc/years/import.tpl');
