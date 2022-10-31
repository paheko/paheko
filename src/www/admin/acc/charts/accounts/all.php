<?php
namespace Garradin;

use Garradin\Accounting\Charts;

require_once __DIR__ . '/../../_inc.php';

$chart = null;

if ($id = (int)qg('id')) {
	$chart = Charts::get($id);
}
elseif (CURRENT_YEAR_ID) {
	$year = $current_year;
	$chart = $year->chart();
}

if (!$chart) {
	throw new UserException('Aucun plan comptable spécifié');
}

$accounts = $chart->accounts();

$form->runIf('bookmark', function () use ($accounts) {
	$b = f('bookmark');

	if (!is_array($b) || empty($b)) {
		return;
	}

	$id = key($b);
	$value = current($b);
	$a = $accounts->get($id);
	$a->bookmark = (bool) $value;
	$a->save();
}, null, Utils::getSelfURI());


$list = $accounts->list();
$list->setTitle($chart->label);
$list->loadFromQueryString();

$tpl->assign('chart', $chart);
$tpl->assign('list', $accounts->list());

$tpl->display('acc/charts/accounts/all.tpl');
