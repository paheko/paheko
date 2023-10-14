<?php

namespace Paheko;

use Paheko\Files\Files;

/**
 * This file *only* applies to setups having updated to 1.3.0 RC1-4
 * as the filesystem handling was not working correctly
 */

$db->beginSchemaUpdate();
$db->dropIndexes();

$db->import(ROOT . '/include/migrations/1.3/1.3.0-rc5.sql');

$db->commitSchemaUpdate();

if (FILE_STORAGE_BACKEND == 'FileSystem' && file_exists(FILE_STORAGE_CONFIG)) {
	// Trash works differently now
	$db->exec('UPDATE files SET trash = NULL WHERE trash IS NOT NULL;');

	rename(FILE_STORAGE_CONFIG, FILE_STORAGE_CONFIG . '.deprecated');
	Files::disableVersioning();

	foreach (Files::all() as $file) {
		if ($file->isDir()) {
			continue;
		}

		// Copy files from old location to new
		$path = sprintf(FILE_STORAGE_CONFIG . '.deprecated/%.2s/%1$s', md5($file->id()));

		if (!file_exists($path)) {
			$file->delete();
			continue;
		}

		$file->store(compact('path'));
		Utils::safe_unlink($path);
	}
}
