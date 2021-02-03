<?php

namespace Garradin\Files\Storage;

use Garradin\Entities\Files\File;

use Garradin\Static_Cache;
use Garradin\DB;

use KD2\DB\EntityManager as EM;

use const Garradin\DB_FILE;

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
		$cache_id = 'files.' . sha1($path);

		if (!Static_Cache::exists($cache_id))
		{
			$db = DB::getInstance();
			$id = $db->firstColumn('SELECT c.rowid FROM files_contents c INNER JOIN files_meta f ON f.content_id = c.id
				WHERE f.path = ? AND f.name = ?;', dirname($path), basename($path));

			if (!$id) {
				throw new \LogicException('There is no file with content_id = ' . $id);
			}

			$blob = $db->openBlob('files_contents', 'content', (int)$id);
			Static_Cache::storeFromPointer($cache_id, $blob);
			fclose($blob);
		}

		return Static_Cache::getPath($cache_id);
	}

	static public function store(string $destination, ?string $source_path, ?string $source_content): bool
	{
		if (!isset($_source_path) && !isset($_source_content)) {
			throw new \InvalidArgumentException('Either source_path or source_content must be supplied');
		}

		$db = DB::getInstance();

		$hash = $source_path ? sha1_file($source_path) : sha1($source_content);

		$content_id = $db->firstColumn('SELECT id FROM files_contents WHERE hash = ?;', $hash);

		if (!$content_id) {
			$db->preparedQuery('INSERT OR IGNORE INTO files_contents (hash, content, size) VALUES (?, zeroblob(?), ?);',
				$file->hash, $file->size, $file->size);

			$content_id = (int) $db->lastInsertId();

			$blob = $db->openBlob('files_contents', 'content', $content_id, 'main', SQLITE3_OPEN_READWRITE);

			if (null !== $content) {
				fwrite($blob, $content);
			}
			else {
				fwrite($blob, file_get_contents($path));
			}

			fclose($blob);
		}

		$db->insert('files_meta', [
			'content_id' => $content_id,
			'type'       => $type,
			'name'       => basename($destination),
			'path'       => dirname($destination),
			'modified'   => new \DateTime,
			'image'      => $is_image,
		]);

		return true;
	}

	static public function list(string $path): array
	{
		File::validatePath($path);
		$db = DB::getInstance();

		$level = substr_count($path, '/');

		return DB::getInstance()->get('SELECT f.name, CAST(strftime(\'%s\', f.modified) AS int) AS modified, f.type, f.path, c.size
			FROM files_meta f LEFT JOIN files_contents c ON f.content_id = c.id
			WHERE path = ?
			ORDER BY type = ? DESC, name COLLATE NOCASE;',
			$path, File::TYPE_DIRECTORY);
	}

	static public function getPath(string $path): ?string
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

		$file = $db->first('SELECT f.id, c.hash, f.type
			FROM files_meta f INNER JOIN files_contents c ON c.id = f.content_id
			WHERE f.path = ? AND f.name = ?;', dirname($path), basename($path));

		if ($file->type == File::TYPE_DIRECTORY) {
			return self::_recursiveDelete($path);
		}

		$is_used_by_others = $db->firstColumn('SELECT 1 FROM files_meta WHERE id != ? AND hash = ?;', $file->id, $file->hash);

		// Don't delete yet, if this hash is still used by other files
		if ($is_used_by_others) {
			return true;
		}

		$cache_id = 'files.' . $file->hash;
		Static_Cache::remove($cache_id);

		return $db->delete('files_contents', 'hash = ?', (int)$file->hash);
	}

	static public function move(string $old_path, string $new_path): bool
	{
		$db = DB::getInstance();

		// Rename/move single file/directory
		$db->preparedQuery('UPDATE files_meta SET path = ?, name = ? WHERE path = ? AND name = ?;',
			dirname($new_path), basename($new_path), dirname($old_path), basename($old_path));

		$is_dir = $db->test('files_meta', 'type = ? AND path = ? AND name = ?',
			File::TYPE_DIRECTORY, dirname($old_path), basename($old_path));

		if (!$is_dir) {
			return true;
		}

		// Rename any sub-directories and files
		$db->preparedQuery('UPDATE files_meta SET path = ? || SUBSTR(path, 1, ?) WHERE path LIKE ? AND path != AND name != ?;',
			dirname($new_path), strlen(dirname($old_path)),
			dirname($old_path) . '/%', basename($old_path), dirname($old_path));

		return true;
	}

	static public function exists(string $path): bool
	{
		return DB::getInstance()->test('files_meta', 'path = ? AND name = ?', dirname($path), basename($path));
	}

	static public function size(string $path): ?int
	{
		 $size = DB::getInstance()->firstColumn('SELECT c.size
		 	FROM files_meta f INNER JOIN files_contents c ON c.id = f.content_id
		 	WHERE f.path = ? ANd f.name = ?;', dirname($path), basename($path));
		 return (int) $size ?: null;
	}

	static public function stat(string $path): ?array
	{
		$result = DB::getInstance()->first('SELECT path, name, c.size, CAST(strftime(\'%s\', f.modified) AS int) AS modified, f.type
			FROM files_meta f INNER JOIN files_contents c ON c.id = f.content_id
			WHERE f.path = ? AND f.name = ?;', dirname($path), basename($path));

		return $result ? (array) $result : null;
	}

	static public function mkdir(string $path): bool
	{
		$db = DB::getInstance();

		// Recursive mkdir of parent directories
		while ($test_path = dirname($path)) {
			if (!$db->test('files_meta', 'path = ? AND name = ?', dirname($test_path), basename($test_path))) {
				self::mkdir($test_path);
			}
		}

		return $db->insert('files_meta', [
			'content_id' => null,
			'modified'   => null,
			'type'       => File::TYPE_DIRECTORY,
			'path'       => dirname($path),
			'name'       => basename($path),
		]);
	}

	static public function modified(string $path): ?int
	{
		$result = DB::getInstance()->firstColumn('SELECT strftime(\'%s\', modified) FROM files_meta WHERE path = ? AND name = ?;',
			dirname($path), basename($path));

		return (int) $result ?: null;
	}

	static public function getTotalSize(): int
	{
		return (int) DB::getInstance()->firstColumn('SELECT SUM(LENGTH(content)) FROM files_contents;');
	}

	static public function getQuota(): int
	{
		return disk_total_space(DATA_ROOT);
	}

	static public function sync(): void
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

	static public function reset(): void
	{
		$db = DB::getInstance();
		$db->exec('DELETE FROM files_contents; VACUUM;');
	}

	static public function lock(): void
	{
		DB::getInstance()->exec('INSERT INTO files_contents (hash, content, size) VALUES (\'.lock\', \'.lock\', 5);');
	}

	static public function unlock(): void
	{
		DB::getInstance()->exec('DELETE FROM files_contents WHERE hash = \'.lock\';');
	}

	static public function checkLock(): void
	{
		$lock = DB::getInstance()->firstColumn('SELECT 1 FROM files_contents WHERE hash = \'.lock\';');

		if ($lock) {
			throw new \RuntimeException('File storage is locked');
		}
	}
}
