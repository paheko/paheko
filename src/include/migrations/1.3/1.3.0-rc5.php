<?php

namespace Paheko;

$db->import(ROOT . '/include/migrations/1.3/1.3.0-rc5.sql');

if (FILE_STORAGE_BACKEND == 'FileSystem') {
	rename(FILE_STORAGE_CONFIG, FILE_STORAGE_CONFIG . '.deprecated');

	foreach (Files::all() as $file) {
		if ($file->isDir()) {
			continue;
		}

		// Copy files from old location to new
		$path = sprintf(FILE_STORAGE_CONFIG . '.deprecated/%.2s/%1$s', md5($file->id()));
		$modified = clone $file->modified;
		$file->store(compact('path'));
		Utils::safe_unlink($path);
	}
}
