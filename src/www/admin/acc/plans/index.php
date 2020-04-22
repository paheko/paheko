<?php
namespace Garradin;

use Garradin\Accounting\Plans;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$plans = new Plans;

$tpl->assign('list', $plans->list());
$tpl->assign('plans_groupped', $plans->listByCountry());
$tpl->assign('country_list', Utils::getCountryList());

$tpl->display('admin/acc/plans/index.tpl');
