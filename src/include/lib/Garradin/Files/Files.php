<?php

namespace Garradin\Files;

use Garradin\Static_Cache;
use Garradin\DB;

use const Garradin\FILE_STORAGE_BACKEND;

class Files
{
	static public function callStorage(string $function, ...$args)
	{
		$storage = FILE_STORAGE_BACKEND ?? 'SQLite';
		$class_name = get_class(__NAMESPACE__ . '\\Backend\\' . $storage);
		return call_user_func_array([$class_name, $function], $args);
	}

	static public function migrateStorage(string $from, string $to)
	{
		$res = EM::getInstance(File::class)->iterate('SELECT * FROM @TABLE;');

		$from = get_class(__NAMESPACE__ . '\\Backend\\' . $from);
		$to = get_class(__NAMESPACE__ . '\\Backend\\' . $to);

		foreach ($res as $file) {
			$from_path = call_user_func([$from, 'path'], $file);
			call_user_func([$to, 'store'], $file, $from_path);
		}
	}
}

