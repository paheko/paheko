<?php
namespace Garradin;

use Garradin\Entities\Accounting\Chart;
use Garradin\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$tpl->assign('list', Charts::list());

if ($session->canAccess('compta', Membres::DROIT_ADMIN)) {
	if (f('new') && $form->check('acc_charts_new')) {
		try {
			$chart = new Chart;
			$chart->importForm();
			$chart->save();

			if (f('copy')) {
				$chart->accounts()->copyFrom((int) f('copy'));
			}

			Utils::redirect(Utils::getSelfURI(false));
		}
		catch (UserException $e) {
			$form->addError($e->getMessage());
		}
	}

	$tpl->assign('from', (int)qg('from'));
	$tpl->assign('charts_groupped', Charts::listByCountry());
	$tpl->assign('country_list', Utils::getCountryList());
}

$tpl->display('acc/charts/index.tpl');
