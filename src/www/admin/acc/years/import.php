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

if (f('cancel')) {
	$session->set('acc_import_csv', null);
}

$csv_file = $session->get('acc_import_csv');

if (f('import') && $csv_file && $form->check('acc_years_import_' . $year->id(), ['translate' => 'array|required']))
{
	try {
		Transactions::importArray($year, $csv_file, f('translate'), (int) f('skip_first_line'), $user->id);
		$session->set('acc_import_csv', null);
		Utils::redirect(ADMIN_URL . 'acc/years/');
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}
elseif (f('import') && $form->check('acc_years_import_' . $year->id(), ['file' => 'file|required']))
{
	try {
		if (f('type') === 'csv') {
			$csv = CSV::readAsArray($_FILES['file']['tmp_name']);
			$session->set('acc_import_csv', $csv);
			Utils::redirect(Utils::getSelfURI());
		}
		else {
			Transactions::importCSV($year, $_FILES['file'], $user->id);
		}

		Utils::redirect(ADMIN_URL . 'acc/years/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$csv_first_line = !empty($csv_file) ? reset($csv_file) : null;

$tpl->assign('columns', implode(', ', array_intersect_key(Transactions::POSSIBLE_CSV_COLUMNS, array_flip(Transactions::MANDATORY_CSV_COLUMNS))));
$tpl->assign('other_columns', implode(', ', array_diff_key(Transactions::POSSIBLE_CSV_COLUMNS, array_flip(Transactions::MANDATORY_CSV_COLUMNS))));
$tpl->assign('possible_columns', Transactions::POSSIBLE_CSV_COLUMNS);
$tpl->assign(compact('csv_file', 'year', 'csv_first_line'));

$tpl->display('acc/years/import.tpl');
