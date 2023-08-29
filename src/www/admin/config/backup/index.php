<?php
namespace Paheko;

use Paheko\Backup;
use Paheko\Files\Files;

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
}, $csrf_key, '!config/backup/?msg=BACKUP_CREATED');

// Download all files as ZIP
$form->runIf('zip', function () {
	Files::zipAll();
	exit;
}, $csrf_key);

$ok = qg('ok'); // return message
$db_size = Backup::getDBSize();
$files_size = Files::getUsedQuota();

$tpl->assign(compact('ok', 'db_size', 'files_size', 'csrf_key'));

$tpl->display('config/backup/index.tpl');
