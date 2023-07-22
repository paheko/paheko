<?php
namespace Paheko;

use Paheko\Accounting\Charts;

require_once __DIR__ . '/../_inc.php';

$chart = Charts::get((int) qg('id'));

if (!$chart) {
	throw new UserException("Le plan comptable demandé n'existe pas.");
}

CSV::export(
	qg('format'),
	sprintf('Plan comptable - %s - %s', Config::getInstance()->get('org_name'), $chart->label),
	$chart->export(),
	$chart::COLUMNS
);
