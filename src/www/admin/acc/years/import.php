<?php
namespace Garradin;

use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if (qg('export')) {
	Utils::export(
		qg('export'),
		sprintf('Export comptable - %s', $year->label),
		Transactions::export($year->id())
	);
	exit;
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

if (f('import') && $form->check('acc_years_import_' . $year->id()))
{
	try {
		$year->import($year, $_FILES['csv']);

		Utils::redirect(ADMIN_URL . 'acc/years/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$csv_file = null;

$tpl->assign('columns', Transactions::EXPECTED_CSV_COLUMNS);
$tpl->assign(compact('csv_file'));

$tpl->display('acc/years/import.tpl');
