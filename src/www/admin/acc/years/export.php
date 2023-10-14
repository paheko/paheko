<?php
namespace Paheko;

use Paheko\Accounting\Export;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

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
	Export::export($year, $format, $type);
	exit;
}

$examples = Export::getExamples($year);

$types = [
	Export::FULL => [
		'label' => 'Complet (comptabilité d\'engagement)',
		'help' => '(Conseillé pour transfert vers un autre logiciel) Chaque ligne reprend toutes les informations de la ligne et de l\'écriture.',
	],
	Export::GROUPED => [
		'label' => 'Complet groupé',
		'help' => 'Les colonnes de l\'écriture ne sont pas répétées pour chaque ligne.',
	],
	Export::SIMPLE => [
		'label' => 'Simplifié (comptabilité de trésorerie)',
		'help' => 'Les écritures avancées ne sont pas inclues dans cet export.',
	],
	Export::FEC => [
		'label' => 'FEC (Fichier des Écritures Comptables)',
		'help' => 'Format standard de l\'administration française.',
	],
];

$tpl->assign(compact('year', 'examples', 'types'));

$tpl->display('acc/years/export.tpl');
