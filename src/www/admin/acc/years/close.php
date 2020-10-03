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

$rules = [
	'end_date' => 'date_format:d/m/Y|required',
];

if (f('close') && $form->check('acc_years_close_' . $year->id()))
{
	try {
		$year->close();
		$year->save();
		$session->set('acc_year', null);

		Utils::redirect(ADMIN_URL . 'acc/years/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign('year', $year);

$tpl->display('acc/years/close.tpl');
