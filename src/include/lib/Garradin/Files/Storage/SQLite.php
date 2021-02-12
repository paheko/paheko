<?php

namespace Garradin\Files\Storage;

use Garradin\Entities\Files\File;

use Garradin\Static_Cache;
use Garradin\DB;

use KD2\DB\EntityManager as EM;

use const Garradin\{DB_FILE, DATA_ROOT};

class SQLite implements StorageInterface
{
	static public function configure(?string $config): void
	{
	}

	/**
	 * Renvoie le chemin vers le fichier local en cache, et le crée s'il n'existe pas
	 * @return string Chemin local
	 */
	static protected function _getFilePathFromCache(string $path): string
	{
		$cache_id = 'files.' . md5($path);

		if (!Static_Cache::exists($cache_id))
		{
			$db = DB::getInstance();
			$id = $db->firstColumn('SELECT rowid FROM files WHERE path = ? AND name = ?;', dirname($path), basename($path));

			if (!$id) {
				throw new \LogicException('There is no file with path = ' . $path);
			}

			$blob = $db->openBlob('files', 'content', (int)$id);
			Static_Cache::storeFromPointer($cache_id, $blob);
			fclose($blob);
		}

		return Static_Cache::getPath($cache_id);
	}

	static public function store(File $file, ?string $source_path, ?string $source_content): bool
	{
		if (!isset($source_path) && !isset($source_content)) {
			throw new \InvalidArgumentException('Either source_path or source_content must be supplied');
		}

		$db = DB::getInstance();

		$st = $db->preparedQuery('INSERT OR REPLACE INTO files (path, name, type, modified, size, content) VALUES (?, ?, ?, ?, ?, zeroblob(?));',
			$file->path, $file->name, $file->type, new \DateTime, $file->size, $file->size);

		$rowid = $db->firstColumn('SELECT rowid FROM files WHERE path = ? AND name = ?;', $file->path, $file->name);

		$blob = $db->openBlob('files', 'content', $rowid, 'main', \SQLITE3_OPEN_READWRITE);

		if (null !== $source_content) {
			fwrite($blob, $source_content);
		}
		else {
			fwrite($blob, file_get_contents($source_path));
		}

		fclose($blob);

		$cache_id = 'files.' . md5($file->path());
		Static_Cache::remove($cache_id);

		return true;
	}

	static public function list(string $path): array
	{
		$db = DB::getInstance();

		$st = DB::getInstance()->preparedQuery('SELECT name, CAST(strftime(\'%s\', modified) AS int) AS modified, type, path, size
			FROM files
			WHERE path = ?
			ORDER BY type = ? DESC, name COLLATE NOCASE;',
			$path, File::TYPE_DIRECTORY);

		$out = [];

		while ($row = $st->fetchArray(\SQLITE3_ASSOC)) {
			$out[] = $row;
		}

		return $out;
	}

	static public function getFullPath(string $path): ?string
	{
		return self::_getFilePathFromCache($path);
	}

	static public function display(string $path): void
	{
		readfile(self::getFilePathFromCache($path));
	}

	static public function fetch(string $path): string
	{
		return file_get_contents(self::_getFilePathFromCache($path));
	}

	static public function delete(string $path): bool
	{
		$db = DB::getInstance();

		$cache_id = 'files.' . md5($path);
		Static_Cache::remove($cache_id);

		return $db->delete('files', 'path = ? AND name = ?;', dirname($path), basename($path));
	}

	static public function move(string $old_path, string $new_path): bool
	{
		$db = DB::getInstance();

		// Rename/move single file/directory
		$db->preparedQuery('UPDATE files SET path = ?, name = ? WHERE path = ? AND name = ?;',
			dirname($new_path), basename($new_path), dirname($old_path), basename($old_path));

		return true;
	}

	static public function exists(string $path): bool
	{
		return DB::getInstance()->test('files', 'path = ? AND name = ?', dirname($path), basename($path));
	}

	static public function size(string $path): ?int
	{
		 $size = DB::getInstance()->firstColumn('SELECT size FROM files WHERE path = ? AND name = ?;', dirname($path), basename($path));
		 return (int) $size ?: null;
	}

	static public function stat(string $path): ?array
	{
		$result = DB::getInstance()->first('SELECT path, name, size, CAST(strftime(\'%s\', modified) AS int) AS modified, type
			FROM files
			WHERE path = ? AND name = ?;', dirname($path), basename($path));

		return $result ? (array) $result : null;
	}

	static public function mkdir(string $path): bool
	{
		$db = DB::getInstance();

		// Recursive mkdir of parent directories
		while ($test_path = dirname($path)) {
			if (!$db->test('files', 'path = ? AND name = ?', dirname($test_path), basename($test_path))) {
				self::mkdir($test_path);
			}
		}

		return $db->insert('files', [
			'type'       => File::TYPE_DIRECTORY,
			'path'       => dirname($path),
			'name'       => basename($path),
		]);
	}

	static public function modified(string $path): ?int
	{
		$result = DB::getInstance()->firstColumn('SELECT strftime(\'%s\', modified) FROM files WHERE path = ? AND name = ?;', dirname($path), basename($path));

		return (int) $result ?: null;
	}

	static public function getTotalSize(): int
	{
		return (int) DB::getInstance()->firstColumn('SELECT SUM(size) FROM files;');
	}

	/**
	 * @see https://www.crazyws.fr/dev/fonctions-php/fonction-disk-free-space-et-disk-total-space-pour-ovh-2JMH9.html
	 * @see https://github.com/jdel/sspks/commit/a890e347f32e9e3e50a0dd82398947633872bf38
	 */
	static public function getQuota(): int
	{
		return @disk_total_space(self::_getRoot()) ?: \PHP_INT_MAX;
	}

	static public function reset(): void
	{
		$db = DB::getInstance();
		$db->exec('DELETE FROM files; VACUUM;');
	}

	static public function lock(): void
	{
		DB::getInstance()->exec('INSERT INTO files (name, path) VALUES (\'.lock\', \'.lock\');');
	}

	static public function unlock(): void
	{
		DB::getInstance()->exec('DELETE FROM files WHERE path = \'.lock\';');
	}

	static public function checkLock(): void
	{
		$lock = DB::getInstance()->firstColumn('SELECT 1 FROM files WHERE path = \'.lock\';');

		if ($lock) {
			throw new \RuntimeException('File storage is locked');
		}
	}
}
