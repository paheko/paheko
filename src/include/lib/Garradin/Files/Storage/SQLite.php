<?php

namespace Garradin\Files\Storage;

use Garradin\Entities\Files\File;
use Garradin\Files\Files;

use Garradin\DB;
use Garradin\Utils;

use KD2\DB\EntityManager as EM;

use const Garradin\{DB_FILE, DATA_ROOT};

class SQLite implements StorageInterface
{
	static public function configure(?string $config): void
	{
	}

	static public function getReadOnlyPointer(File $file)
	{
		$db = DB::getInstance();

		try {
			$blob = $db->openBlob('files_contents', 'content', $file->id());
		}
		catch (\Exception $e) {
			if (!strstr($e->getMessage(), 'no such rowid')) {
				throw $e;
			}

			return null;
		}

		return $blob;
	}

	static public function storePath(File $file, string $path): bool
	{
		return self::store($file, compact('path'));
	}

	static public function storeContent(File $file, string $content): bool
	{
		return self::store($file, compact('content'));
	}

	static public function storePointer(File $file, $pointer): bool
	{
		return self::store($file, compact('pointer'));
	}

	static protected function store(File $file, array $source): bool
	{
		if (!isset($source['path']) && !isset($source['content']) && !isset($source['pointer'])) {
			throw new \InvalidArgumentException('Unknown source type');
		}
		elseif (count($source) != 1) {
			throw new \InvalidArgumentException('Invalid source type');
		}

		$content = $path = $pointer = null;
		extract($source);

		$db = DB::getInstance();

		$file->save();

		$id = $file->id();

		$db->preparedQuery('INSERT OR REPLACE INTO files_contents (id, content) VALUES (?, zeroblob(?));',
			$id, $file->size);

		$blob = $db->openBlob('files_contents', 'content', $id, 'main', \SQLITE3_OPEN_READWRITE);

		if (null !== $content) {
			fwrite($blob, $content);
		}
		elseif ($path) {
			$pointer = fopen($path, 'rb');
		}

		if ($pointer) {
			while (!feof($pointer)) {
				fwrite($blob, fread($pointer, 8192));
			}

			if ($path) {
				fclose($pointer);
			}
		}

		fclose($blob);

		return true;
	}

	static public function getLocalFilePath(File $file): ?string
	{
		return null;
	}

	static public function delete(File $file): bool
	{
		// Nothing to do, files_contents is deleted when files row is deleted (cascade)
		return true;
	}

	/**
	 * @see https://www.crazyws.fr/dev/fonctions-php/fonction-disk-free-space-et-disk-total-space-pour-ovh-2JMH9.html
	 * @see https://github.com/jdel/sspks/commit/a890e347f32e9e3e50a0dd82398947633872bf38
	 */
	static public function getQuota(): float
	{
		$quota = @disk_total_space(DATA_ROOT);
		return $quota === false ? (float) \PHP_INT_MAX : (float) $quota;
	}

	static public function getRemainingQuota(): float
	{
		$quota = @disk_free_space(DATA_ROOT);
		return $quota === false ? (float) \PHP_INT_MAX : (float) $quota;
	}

	static public function truncate(): void
	{
		$db = DB::getInstance();
		$db->exec('DELETE FROM files_contents; DELETE FROM files; VACUUM;');
	}

	static public function lock(): void
	{
		DB::getInstance()->exec('CREATE TABLE IF NOT EXISTS files_lock (lock);');
	}

	static public function unlock(): void
	{
		DB::getInstance()->exec('DROP TABLE IF EXISTS files_lock;');
	}

	static public function isLocked(): bool
	{
		return DB::getInstance()->test('sqlite_master', 'name = ? AND type = ?', 'files_lock', 'table');
	}
}
