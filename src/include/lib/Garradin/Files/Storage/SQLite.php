<?php

namespace Garradin\Files\Storage;

use Garradin\Entities\Files\File;

use Garradin\Static_Cache;
use Garradin\DB;

use const Garradin\DB_FILE;

class SQLite implements StorageInterface
{
	/**
	 * Renvoie le chemin vers le fichier local en cache, et le crée s'il n'existe pas
	 * @return string Chemin local
	 */
	static protected function _getFilePathFromCache(File $file): string
	{
		$cache_id = 'files.' . $file->hash;

		if (!Static_Cache::exists($cache_id))
		{
			$db = DB::getInstance();
			$id = $db->firstColumn('SELECT id FROM files_contents WHERE hash = ?;', $file->hash);
			$blob = $db->openBlob('files_contents', 'content', (int)$id);
			Static_Cache::storeFromPointer($cache_id, $blob);
			fclose($blob);
		}

		return Static_Cache::getPath($cache_id);
	}

	static public function store(File $file, ?string $path, ?string $content): bool
	{
		$db = DB::getInstance();

		if ($db->test('files_contents', 'hash = ?', $file->hash)) {
			return true;
		}

		$db->preparedQuery('INSERT INTO files_contents (hash, content, size) VALUES (?, zeroblob(?), ?);',
			$file->hash, $file->size, $file->size);

		$id = (int) $db->lastInsertId();

		$blob = $db->openBlob('files_contents', 'content', $id, 'main', SQLITE3_OPEN_READWRITE);

		if (null !== $content) {
			fwrite($blob, $content);
		}
		else {
			fwrite($blob, file_get_contents($path));
		}

		fclose($blob);

		return true;
	}

	static public function list(string $path): ?array
	{
		return null;
	}

	static public function getPath(File $file): ?string
	{
		return self::_getFilePathFromCache($file);
	}

	static public function display(File $file): void
	{
		readfile(self::getFilePathFromCache($file));
	}

	static public function fetch(File $file): string
	{
		return file_get_contents(self::_getFilePathFromCache($file));
	}

	static public function delete(File $file): bool
	{
		$cache_id = 'files.' . $file->hash;
		Static_Cache::remove($cache_id);

		return DB::getInstance()->delete('files_contents', 'hash = ?', (int)$file->hash);
	}

	static public function move(File $old_file, File $new_file): bool
	{
		return true;
	}

	static public function getTotalSize(): int
	{
		return (int) DB::getInstance()->firstColumn('SELECT SUM(size) FROM files_contents;');
	}

	static public function getQuota(): int
	{
		return disk_total_space(dirname(DB_FILE));
	}

	static public function cleanup(): void
	{
		$db = DB::getInstance();

		$sql = 'SELECT c.id, c.hash FROM files_contents c LEFT JOIN files f ON f.hash = c.hash WHERE f.hash IS NULL;';
		$ids = [];

		foreach ($db->iterate($sql) as $row) {
			$cache_id = 'files.' . $row->hash;
			Static_Cache::remove($cache_id);
			$ids[] = $row->id;
		}

		$db->delete('files_contents', $db->where('id', $ids));
	}
}
