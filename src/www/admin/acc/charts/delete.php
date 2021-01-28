<?php
namespace Garradin;

use Garradin\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$chart = Charts::get((int) qg('id'));

if (!$chart) {
	throw new UserException("Le plan comptable demandé n'existe pas.");
}

if (!$chart->canDelete()) {
	throw new UserException("Ce plan comptable ne peut être supprimé car il est lié à des exercices");
}

if (f('delete') && $form->check('acc_charts_delete_' . $chart->id()))
{
	try
	{
		$chart->delete();
		Utils::redirect(sprintf('%sacc/charts/', ADMIN_URL));
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign(compact('chart'));

$tpl->display('acc/charts/delete.tpl');
