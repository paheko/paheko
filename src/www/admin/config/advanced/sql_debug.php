<?php
namespace Garradin;

use KD2\ErrorManager;

require_once __DIR__ . '/../_inc.php';

if (!ENABLE_TECH_DETAILS || !SQL_DEBUG) {
	throw new UserException('Détails techniques ou debug SQL désactivés');
}

DB::getInstance()->disableLog();

if (qg('id'))
{
	$tpl->assign('debug', DB::getDebugSession(qg('id')));
}
else
{
	$tpl->assign('list', DB::getDebugSessionsList());
}

$tpl->display('admin/config/advanced/sql_debug.tpl');
