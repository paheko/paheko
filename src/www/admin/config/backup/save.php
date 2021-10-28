<?php
namespace Garradin;

use Garradin\Files\Files;

require_once __DIR__ . '/../_inc.php';

$s = new Sauvegarde;

// Download database
$form->runIf('download', function () use ($s) {
	$s->dump();
	exit;
}, 'backup_download');

// Download all files as ZIP
$form->runIf('download_files', function () use ($s) {
	$s->dumpFilesZip();
	exit;
}, 'files_download');

// Create local backup
$form->runIf('create', function () use ($s) {
	$s->create();
}, 'backup_create', Utils::getSelfURI(['ok' => 'create']));

$form->runIf('config', function () {
	if (!ENABLE_AUTOMATIC_BACKUPS) {
		return;
	}

	$frequency = (int) f('frequence_sauvegardes');

	if ($frequency < 0 || $frequency > 365) {
		throw new UserException('Fréquence invalide');
	}

	$number = (int) f('nombre_sauvegardes');

	if ($number < 0 || $number > 50) {
		throw new UserException('Nombre de sauvegardes invalide. Le maximum est de 50 sauvegardes.');
	}

	$config = Config::getInstance();
	$config->set('frequence_sauvegardes', $frequency);
	$config->set('nombre_sauvegardes', $number);
	$config->save();
}, 'backup_config', Utils::getSelfURI(['ok' => 'config']));

$db_size = $s->getDBSize();
$files_size = Files::getUsedQuota();

$ok = qg('ok'); // return message

$tpl->assign(compact('ok', 'db_size', 'files_size'));

$tpl->display('admin/config/backup/save.tpl');
