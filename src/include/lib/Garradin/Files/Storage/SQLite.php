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

		$st = $db->preparedQuery('INSERT OR REPLACE INTO files_contents (id, content) VALUES (?, zeroblob(?));',
			$file->id(), $file->size);

		$blob = $db->openBlob('files', 'content', $file->id(), 'main', \SQLITE3_OPEN_READWRITE);

		if (null !== $source_content) {
			fwrite($blob, $source_content);
		}
		else {
			fwrite($blob, file_get_contents($source_path));
		}

		fclose($blob);

		$cache_id = 'files.' . md5($file->pathname());
		Static_Cache::remove($cache_id);

		return true;
	}

	static public function getFullPath(File $file): ?string
	{
		return self::_getFilePathFromCache($file->pathname());
	}

	static public function display(File $file): void
	{
		readfile(self::getFullPath($file));
	}

	static public function fetch(File $file): string
	{
		return file_get_contents(self::getFullPath($file));
	}

	static public function delete(File $file): bool
	{
		$db = DB::getInstance();

		$cache_id = 'files.' . md5($file->pathname());
		Static_Cache::remove($cache_id);

		return $db->delete('files_contents', 'id = ?', $file->id());
	}

	static public function move(File $file, string $new_path): bool
	{
		return true;
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

	static public function getTotalSize(): int
	{
		return (int) DB::getInstance()->firstColumn('SELECT SUM(LENGTH(content)) FROM files_contents;');
	}

	/**
	 * @see https://www.crazyws.fr/dev/fonctions-php/fonction-disk-free-space-et-disk-total-space-pour-ovh-2JMH9.html
	 * @see https://github.com/jdel/sspks/commit/a890e347f32e9e3e50a0dd82398947633872bf38
	 */
	static public function getQuota(): int
	{
		return @disk_total_space(self::_getRoot()) ?: \PHP_INT_MAX;
	}

	static public function truncate(): void
	{
		$db = DB::getInstance();
		$db->exec('DELETE FROM files_contents; VACUUM;');
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

	/**
	 * We don't need to do anything there as everything is already in DB
	 */
	static public function sync(?string $path): void
	{
		return;
	}

	static public function update(File $file): File
	{
		return $file;
	}
}
