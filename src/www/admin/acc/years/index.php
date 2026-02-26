<?php
namespace Paheko;

use Paheko\Accounting\Charts;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

// Redirect to first setup if there are no years
if (!Years::count()) {
	Utils::redirect('!acc/years/first_setup.php');
}

$has_enough_transactions = Transactions::countAll() >= 3;
$list = Years::listWithStats();
$tpl->assign(compact('list', 'has_enough_transactions'));

$tpl->display('acc/years/index.tpl');
