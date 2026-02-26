<?php
namespace Paheko;

use Paheko\Accounting\Export;
use Paheko\Accounting\Years;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$year_id = intval($_GET['year'] ?? 0);
$year = Years::get($year_id);

if (!$year) {
	throw new UserException("L'exercice demandé n'existe pas.");
}

$csrf_key = 'year_download_' . $year->id();

$form->runIf('download', function () use ($year) {
	$session = Session::getInstance();
	$year->zipAllAttachments(null, $session);
	exit;
}, $csrf_key);

$reports = [
	'Balance générale'    => 'acc/reports/trial_balance.php?year=' . $year->id(),
	'Journal général'     => 'acc/reports/journal.php?year=' . $year->id(),
	'Grand livre'         => 'acc/reports/ledger.php?year=' . $year->id(),
	'Compte de résultat'  => 'acc/reports/statement.php?year=' . $year->id(),
	'Bilan'               => 'acc/reports/balance_sheet.php?year=' . $year->id(),
];

$pdf = Utils::canDoPDF();
$files = [
	'Fichiers joints aux écritures' => [
		'url'     => ADMIN_URL . 'acc/years/download.php?year=' . $year->id(),
		'checked' => false,
		'ext'     => 'zip',
	],
	'Export conforme FEC' => [
		'url'     => ADMIN_URL . 'acc/years/export.php?type=fec&format=fec&year=' . $year->id(),
		'ext'     => 'txt',
		'checked' => true,
	],
	'Export complet pour tableur' => [
		'url'     => ADMIN_URL . 'acc/years/export.php?type=full&format=ods&year=' . $year->id(),
		'ext'     => 'ods',
		'checked' => true,
	],
];

foreach ($reports as $name => $url) {
	$files[$name] = [
		'url'     => ADMIN_URL . $url . ($pdf ? '&_pdf' : ''),
		'ext'     => $pdf ? 'pdf' : 'html',
		'checked' => true,
	];
}

$tpl->assign(compact('year', 'files', 'csrf_key'));

$tpl->display('acc/years/download.tpl');
