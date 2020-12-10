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

	static public function migrateStorage(string $from, string $to): void
	{
		$res = EM::getInstance(File::class)->iterate('SELECT * FROM @TABLE;');

		$from = get_class(__NAMESPACE__ . '\\Backend\\' . $from);
		$to = get_class(__NAMESPACE__ . '\\Backend\\' . $to);

		foreach ($res as $file) {
			$from_path = call_user_func([$from, 'path'], $file);
			call_user_func([$to, 'store'], $file, $from_path);
		}
	}

	static public function deleteOrphanFiles()
	{
		$db = DB::getInstance();
		$sql = 'SELECT f.* FROM files f LEFT JOIN files_links l ON f.id = l.id WHERE l.id IS NULL;';

		foreach ($db->iterate($sql) as $file) {
			$f = new Fichiers($file->id, (array) $file);
			$f->remove();
		}

		// Remove any left-overs
		$db->exec('DELETE FROM files_contents WHERE hash NOT IN (SELECT DISTINCT hash FROM files);');
	}

	static public function deleteLinkedFiles(string $type, ?int $value = null)
	{
		foreach (self::iterateLinkedTo($type, $value) as $file) {
			$file->delete();
		}

		self::deleteOrphanFiles();
	}

	static public function iterateLinkedTo(string $type, ?int $value = null)
	{
		$where = $value ? sprintf('l.%s = %d', $value) : sprintf('l.%s IS NOT NULL');
		$sql = sprintf('SELECT f.* FROM @TABLE f INNER JOIN files_links l ON l.id = f.id WHERE %s;', $where);

		return EM::getInstance(File::class)->iterate($sql);
	}

	static public function generatePathsIndex(): void
	{
		$all = DB::getInstance()->getAssoc('SELECT path, path FROM files GROUP BY path;');
		$paths = [];

		foreach ($all as $path) {
			$path = explode('/', $path);

			foreach ($path as $part) {
				
			}
		}
	}
}

