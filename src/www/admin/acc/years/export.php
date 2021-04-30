<?php
namespace Garradin;

use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year_id = (int) qg('id') ?: CURRENT_YEAR_ID;

if ($year_id === CURRENT_YEAR_ID) {
	$year = $current_year;
}
else {
	$year = Years::get($year_id);
}

if (!$year) {
	throw new UserException("L'exercice demandé n'existe pas.");
}

$format = qg('format');
$type = qg('type');

if (null !== $format && null !== $type) {
	Transactions::export($year, $format, $type);
	exit;
}

$tpl->assign(compact('year'));

$tpl->display('acc/years/export.tpl');
