<?php

namespace Garradin;

use Garradin\Accounting\Reports;

require_once __DIR__ . '/_inc.php';

$journal = Reports::getJournal($criterias);

$tpl->assign(compact('journal'));

if ($f = qg('export')) {
	// FIXME: need support for rowspan
	$tpl->assign('caption', 'Journal général');
	$table = $tpl->fetch('acc/reports/_journal.tpl');

	CSV::exportHTML($f, $table, 'Journal général');

	return;
}

$tpl->display('acc/reports/journal.tpl');
