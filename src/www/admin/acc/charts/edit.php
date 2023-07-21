<?php
namespace Paheko;

use Paheko\Entities\Accounting\Chart;
use Paheko\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$chart = Charts::get((int) qg('id'));

if (!$chart) {
	throw new UserException("Le plan comptable demandé n'existe pas.");
}

$csrf_key = 'acc_charts_edit_' . $chart->id();

$form->runIf('save', function() use ($chart) {
	$chart->importForm();
	$chart->set('archived', (bool) f('archived'));
	$chart->save();
}, $csrf_key, '!acc/charts/');

$tpl->assign(compact('chart'));

$tpl->display('acc/charts/edit.tpl');
