<?php
namespace Garradin;

use Garradin\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$chart = Charts::get((int) qg('id'));

if (!$chart) {
	throw new UserException("Le plan comptable demandé n'existe pas.");
}

if (f('save') && $form->check('acc_charts_edit_' . $chart->id()))
{
	try
	{
		$chart->importForm();
		$chart->save();

		Utils::redirect(sprintf('%sacc/charts/', ADMIN_URL));
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign(compact('chart'));
$tpl->assign('country_list', Utils::getCountryList());

$tpl->display('acc/charts/edit.tpl');
