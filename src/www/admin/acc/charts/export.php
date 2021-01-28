<?php
namespace Garradin;

use Garradin\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$chart = Charts::get((int) qg('id'));

if (!$chart) {
	throw new UserException("Le plan comptable demandé n'existe pas.");
}

CSV::export(
	null !== qg('ods') ? 'ods' : 'csv',
	sprintf('Plan comptable - %s - %s', Config::getInstance()->get('nom_asso'), $chart->label),
	$chart->accounts()->export()
);
