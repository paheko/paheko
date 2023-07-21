<?php
namespace Paheko;

use Paheko\Accounting\Years;
use Paheko\Accounting\Graph;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

if (!Years::count()) {
	Utils::redirect('!acc/years/first_setup.php');
}

$tpl->assign('graphs', array_slice(Graph::URL_LIST, 0, 3));

$years = Years::listOpen(true);
$tpl->assign('years', $years);
$tpl->assign('first_year', count($years) ? current($years)->id : null);
$tpl->assign('all_years', [null => '-- Tous les exercices'] + Years::listAssoc());
$tpl->assign('last_transactions', Years::listLastTransactions(10, $years));

$tpl->display('acc/index.tpl');
