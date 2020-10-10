<?php
namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$years = new Years;

$tpl->assign('list', $years->list(true));

$tpl->display('acc/years/index.tpl');
