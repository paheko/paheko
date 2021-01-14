<?php

namespace Garradin\Files;

use Garradin\Static_Cache;
use Garradin\DB;
use Garradin\Membres\Session;
use Garradin\Entities\Files\File;
use KD2\DB\EntityManager as EM;

use const Garradin\{FILE_STORAGE_BACKEND, FILE_STORAGE_QUOTA};

class Files
{
	static public function getSystemFile(string $file, string $folder, ?string $subfolder = null): ?File
	{
		$where = Folders::getFolderClause(true, $folder, $subfolder);
		return EM::findOne(File::class, sprintf('SELECT * FROM files WHERE name = ? AND folder_id = (%s) LIMIT 1;', $where), $file);
	}

	static public function listSystemFiles(string $folder, ?string $subfolder = null): array
	{
		$where = Folders::getFolderClause(true, $folder, $subfolder);
		return EM::get(sprintf('SELECT * FROM files WHERE folder_id = (%s) ORDER BY name;', $where));
	}

	static public function callStorage(string $function, ...$args)
	{
		// Check that we can store this data
		if ($function == 'store') {
			$quota = FILE_STORAGE_QUOTA ?: self::callStorage('getQuota');
			$used = self::callStorage('getTotalSize');

			$size = $args[0] ? filesize($args[1]) : strlen($args[2]);

			if (($used + $size) >= $quota) {
				throw new \OutOfBoundsException('File quota has been exhausted');
			}
		}

		$storage = FILE_STORAGE_BACKEND ?? 'SQLite';
		$class_name = __NAMESPACE__ . '\\Storage\\' . $storage;
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

	static public function get(int $id): ?File
	{
		return EM::findOneById(File::class, $id);
	}

	static public function serveFromQueryString(): void
	{
		$id = isset($_GET['id']) ? $_GET['id'] : null;
		$filename = !empty($_GET['file']) ? $_GET['file'] : null;

		$size = null;

		if (empty($id)) {
			header('HTTP/1.1 404 Not Found', true, 404);
			throw new UserException('Fichier inconnu.');
		}

		foreach ($_GET as $key => $value) {
			if (substr($key, -2) == 'px') {
				$size = (int)substr($key, 0, -2);
				break;
			}
		}

		$id = base_convert($id, 36, 10);

		$file = self::get((int) $id);

		if (!$file) {
			header('HTTP/1.1 404 Not Found', true, 404);
			throw new UserException('Ce fichier n\'existe pas.');
		}

		$session = Session::getInstance();

		if ($size) {
			$file->serveThumbnail($session, $size);
		}
		else {
			$file->serve($session);
		}
	}
}

