<?php
namespace Garradin;

use Garradin\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$chart = Charts::get((int) qg('id'));

if (!$chart) {
	throw new UserException("Le plan comptable demandé n'existe pas.");
}

Utils::export(
	null !== qg('ods') ? 'ods' : 'csv',
	sprintf('Plan comptable - %s', $chart->label),
	$chart->accounts()->export()
);
