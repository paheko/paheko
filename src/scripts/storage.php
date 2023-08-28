<?php

namespace Paheko;

use Paheko\Files\Storage;
use Paheko\Entities\Files\File;

if (PHP_SAPI != 'cli' && !defined('\Paheko\ROOT')) {
	die("Wrong call");
}

require_once __DIR__ . '/../include/init.php';

if (FILE_STORAGE_BACKEND === 'SQLite' || !FILE_STORAGE_CONFIG) {
	echo "Invalid: FILE_STORAGE_BACKEND is 'SQLite' or FILE_STORAGE_CONFIG is not set\n";
	exit(1);
}

$command = $_SERVER['argv'][1] ?? null;
$callback = fn (string $action, File $file) => printf("%s: %s\n", $action, $file->path);

if ($command === 'import') {
	Storage::migrate(FILE_STORAGE_BACKEND, 'SQLite', FILE_STORAGE_CONFIG, null, $callback);
}
elseif ($command === 'export') {
	Storage::migrate('SQLite', FILE_STORAGE_BACKEND, null, FILE_STORAGE_CONFIG, $callback);
}
elseif ($command === 'truncate') {
	Storage::truncate('SQLite', null);
	print("Deleted all files contents from database.\n");
}
elseif ($command === 'scan') {
	Storage::sync(null, $callback);
}
else {
	printf("Usage: %s COMMAND
COMMAND can be either:

import
	Import files from configured storage to database

export
	Export files from database to configured storage

truncate
	Delete all files contents from database.
	(No confirmation asked!)

scan
	Update or rebuild files list in database by listing files
	directly from configured storage.

", $_SERVER['argv'][0]);
	exit(1);
}
