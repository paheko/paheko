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
		$cache_id = 'files.' . $file->content_id;

		if (!Static_Cache::exists($cache_id))
		{
			$blob = DB::getInstance()->openBlob('files_contents', 'content', (int)$file->content_id);
			Static_Cache::storeFromPointer($cache_id, $blob);
			fclose($blob);
		}

		return Static_Cache::getPath($cache_id);
	}

	static public function store(File $file, ?string $path, ?string $content): bool
	{
		$db = DB::getInstance();

		if (!$file->content_id) {
			$db->preparedQuery('INSERT INTO files_contents (hash, content) VALUES (?, ?, zeroblob(?));', $file->hash, $file->size);
			$file->content_id = $db->lastInsertId();
		}
		else {
			$cache_id = 'files.' . $file->content_id;

			Static_Cache::remove($cache_id);

			$db->preparedQuery('UPDATE files_contents SET hash = ?, content = zeroblob(?) WHERE id = ?;', $file->hash, $file->size, $file->content_id);
		}

		$blob = $db->openBlob('files_contents', 'content', $file->content_id, 'main', SQLITE3_OPEN_READWRITE);

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
		$cache_id = 'files.' . $file->content_id;
		Static_Cache::remove($cache_id);

		return DB::getInstance()->delete('files_contents', 'id = ?', (int)$file->content_id);
	}

	static public function move(File $old_file, File $new_file): bool
	{
		return true;
	}

	static public function getTotalSize(): int
	{
		return (int) DB::getInstance()->firstColumn('SELECT SUM(LENGTH(content)) FROM files_contents;');
	}

	static public function getQuota(): int
	{
		return disk_total_space(dirname(DB_FILE));
	}
}
