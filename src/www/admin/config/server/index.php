<?php
namespace Paheko;

use Paheko\Files\Storage;
use Paheko\Install;

require_once __DIR__ . '/../_inc.php';

if (!ENABLE_TECH_DETAILS) {
	throw new UserException('Accès invalide', 401);
}

$csrf_key = 'config_server';

if (FILE_STORAGE_BACKEND === 'FileSystem') {
	$form->runIf('import', function () {
		Storage::migrate(FILE_STORAGE_BACKEND, 'SQLite', FILE_STORAGE_CONFIG, null);
	}, $csrf_key, '?msg=OK');

	$form->runIf('export', function () {
		Storage::migrate('SQLite', FILE_STORAGE_BACKEND, null, FILE_STORAGE_CONFIG);
		Storage::truncate('SQLite', null);
	}, $csrf_key, '?msg=OK');

	$form->runIf('scan', function () {
		Storage::sync(null);
	}, $csrf_key, '?msg=OK');
}

$constants = Install::getConstants();

$db_size = DB::getInstance()->firstColumn('SELECT SUM(LENGTH(content)) FROM files_contents;');

$tpl->assign(compact('constants', 'db_size', 'csrf_key'));

$tpl->display('config/server/index.tpl');
