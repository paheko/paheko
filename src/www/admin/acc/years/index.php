<?php
namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ACCES);

$tpl->assign('list', Years::list(true));

$tpl->display('acc/years/index.tpl');
