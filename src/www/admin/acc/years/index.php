<?php
namespace Paheko;

use Paheko\Accounting\Charts;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

if (!Years::count() && !Charts::count()) {
	Utils::redirect('!acc/years/first_setup.php');
}

$list = Years::listWithStats();
$tpl->assign(compact('list'));

$tpl->display('acc/years/index.tpl');
