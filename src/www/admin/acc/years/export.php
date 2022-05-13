<?php
namespace Garradin;

use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year_id = (int) qg('year') ?: CURRENT_YEAR_ID;

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

$examples = Transactions::getExportExamples($year);

$types = [
	Transactions::EXPORT_FULL => [
		'label' => 'Complet (comptabilité d\'engagement)',
		'help' => '(Conseillé pour transfert vers un autre logiciel) Chaque ligne reprend toutes les informations de la ligne et de l\'écriture.',
	],
	Transactions::EXPORT_GROUPED => [
		'label' => 'Complet groupé',
		'help' => 'Idem, sauf que les lignes suivantes ne mentionnent pas les informations de l\'écriture.',
	],
	Transactions::EXPORT_SIMPLE => [
		'label' => 'Simplifié (comptabilité de trésorerie)',
		'help' => 'Les écritures avancées ne sont pas inclues dans cet export.',
	],
];

$tpl->assign(compact('year', 'examples', 'types'));

$tpl->display('acc/years/export.tpl');
