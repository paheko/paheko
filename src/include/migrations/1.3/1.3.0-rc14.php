<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;

$db->beginSchemaUpdate();
$db->import(ROOT . '/include/migrations/1.3/1.3.0-rc14.sql');
$db->commitSchemaUpdate();

// Flatten file hierarchy
function flatten_web_dir(string $path)
{
	foreach (Files::list($path) as $file) {
		if ($file->isDir()) {
			$flatten($file->path);

			if (substr_count($file->path, '/') >= 2) {
				$file->rename(File::CONTEXT_WEB . '/' . $file->name);
			}
		}
	}
}

flatten_web_dir(File::CONTEXT_WEB);
