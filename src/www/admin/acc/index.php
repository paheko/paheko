<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Accounting\Graph;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$tpl->assign('graphs', Graph::URL_LIST);

$years = Years::listOpen(true);
$tpl->assign('years', $years);
$tpl->assign('first_year', count($years) ? current($years)->id : null);
$tpl->assign('all_years', [null => '-- Tous les exercices'] + Years::listAssoc());

$tpl->display('acc/index.tpl');
