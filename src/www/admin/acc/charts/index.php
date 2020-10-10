<?php
namespace Garradin;

use Garradin\Accounting\Charts;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$charts = new Charts;

$tpl->assign('list', $charts->list());
$tpl->assign('charts_groupped', $charts->listByCountry());
$tpl->assign('country_list', Utils::getCountryList());

$tpl->display('acc/charts/index.tpl');
