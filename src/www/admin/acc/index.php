<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Accounting\Graph;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);

$tpl->assign('graphs', Graph::URL_LIST);

$tpl->assign('years', Years::listOpen(true));

$tpl->display('acc/index.tpl');
