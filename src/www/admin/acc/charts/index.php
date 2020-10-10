<?php
namespace Garradin;

use Garradin\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$tpl->assign('list', Charts::list());
$tpl->assign('charts_groupped', Charts::listByCountry());
$tpl->assign('country_list', Utils::getCountryList());

$tpl->display('acc/charts/index.tpl');
