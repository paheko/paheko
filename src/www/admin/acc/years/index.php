<?php
namespace Paheko;

use Paheko\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$list = Years::list(true);

if (!count($list)) {
	Utils::redirect('!acc/years/first_setup.php');
}

$tpl->assign('list', $list);

$tpl->display('acc/years/index.tpl');
