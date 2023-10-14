<?php
namespace Paheko;

use Paheko\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$chart = Charts::get((int) qg('id'));

if (!$chart) {
	throw new UserException("Le plan comptable demandé n'existe pas.");
}

if (!$chart->canDelete()) {
	throw new UserException("Ce plan comptable ne peut être supprimé car il est lié à des exercices");
}

$csrf_key = 'acc_charts_delete_' . $chart->id();

$form->runIf('delete', function () use ($chart) {
	$chart->delete();
}, $csrf_key, '!acc/charts/');

$tpl->assign(compact('chart'));

$tpl->display('acc/charts/delete.tpl');
