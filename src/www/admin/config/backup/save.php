<?php
namespace Garradin;

use Garradin\Backup;
use Garradin\Files\Files;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'backup_save';

// Download database
$form->runIf('download', function () {
	Backup::dump();
	exit;
}, $csrf_key);

// Create local backup
$form->runIf('create', function () {
	Backup::create();
}, $csrf_key, Utils::getSelfURI(['ok' => 'create']));

$form->runIf('config', function () {
	$frequency = (int) f('backup_frequency');

	if ($frequency < 0 || $frequency > 365) {
		throw new UserException('Fréquence invalide');
	}

	$number = (int) f('backup_limit');

	if ($number < 0 || $number > 50) {
		throw new UserException('Nombre de sauvegardes invalide. Le maximum est de 50 sauvegardes.');
	}

	$config = Config::getInstance();
	$config->set('backup_frequency', $frequency);
	$config->set('backup_limit', $number);
	$config->save();
}, $csrf_key, Utils::getSelfURI(['ok' => 'config']));

$db_size = Backup::getDBSize();
$files_size = (FILE_STORAGE_BACKEND == 'SQLite') ? Files::getUsedQuota() : null;

$ok = qg('ok'); // return message

$frequencies = [
	0 => 'Aucun — les sauvegardes automatiques sont désactivées',
	1 => 'Quotidienne, tous les jours',
	7 => 'Hebdomadaire, tous les 7 jours',
	15 => 'Bimensuelle, tous les 15 jours',
	30 => 'Mensuelle',
	90 => 'Trimestrielle',
	365 => 'Annuelle',
];

$tpl->assign(compact('ok', 'db_size', 'files_size', 'frequencies', 'csrf_key'));

$tpl->display('config/backup/save.tpl');
