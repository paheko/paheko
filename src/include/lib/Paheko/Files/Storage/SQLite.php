<?php

namespace Paheko\Files\Storage;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;

use Paheko\DB;
use Paheko\Utils;

use KD2\DB\EntityManager as EM;

use const Paheko\{DB_FILE, DATA_ROOT};

/**
 * This class implements the SQLite file storage.
 *
 * The content of a file is stored as a BLOB inside the database, in the files_contents table.
 * In this case, the 'files' table is no longer just a cache of files metadata,
 * but the only storage of metadata.
 */
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

	static public function touch(File $file, \DateTime $date): void
	{
	}

	static public function delete(File $file): bool
	{
		// Nothing to do, files_contents is deleted when files row is deleted (cascade)
		return true;
	}

	static public function rename(File $file, string $new_path): bool
	{
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
		$db->exec('DELETE FROM files_contents; VACUUM;');
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

	static public function listFiles(?string $path = null): array
	{
		// Doesn't make sense
		throw new \LogicException('SQLite storage cannot list local files');
	}

	static public function cleanup(): void
	{
	}
}
