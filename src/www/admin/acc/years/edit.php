<?php
namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

if ($year->closed) {
	throw new UserException('Impossible de modifier un exercice clôturé.');
}

if (f('edit') && $form->check('acc_years_edit_' . $year->id()))
{
	try {
		$year->importForm();
		$year->save();

		Utils::redirect(ADMIN_URL . 'acc/years/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign('year', $year);

$tpl->display('acc/years/edit.tpl');
