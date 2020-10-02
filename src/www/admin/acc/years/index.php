<?php
namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$years = new Years;

$tpl->assign('list', $years->list());

$tpl->display('acc/years/index.tpl');
