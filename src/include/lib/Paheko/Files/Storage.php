<?php

namespace Paheko\Files;

use Paheko\DB;
use Paheko\Utils;
use Paheko\Entities\Files\File;

use const Paheko\{FILE_STORAGE_BACKEND, FILE_STORAGE_QUOTA, FILE_STORAGE_CONFIG};

class Storage
{
	static public function call(string $backend, string $function, ...$params)
	{
		$backend = __NAMESPACE__ . '\\Storage\\' . $backend;

		if (!class_exists($backend)) {
			throw new \InvalidArgumentException('Invalid storage: ' . $backend);
		}

		return call_user_func([$backend, $function], ...$params);
	}

	/**
	 * Used to re-sync files between the file storage backend and the files table
	 */
	static public function sync(?string $path = null, ?callable $callback = null): void
	{
		if (FILE_STORAGE_BACKEND === 'SQLite') {
			return;
		}

		$db = DB::getInstance();
		$db->begin();

		$cache_files = Files::list($path);
		$local_files = Files::callStorage('listFiles', $path);

		foreach ($local_files as $file) {
			if ($file->type == $file::TYPE_DIRECTORY) {
				self::sync($file->path, $callback);
				unset($cache_files[$file->path]);
				continue;
			}

			$cache_differs = false;
			$cache = $cache_files[$file->path] ?? null;

			if ($cache) {
				if ($cache->modified->getTimestamp() !== $file->modified->getTimestamp()) {
					$cache_differs = true;
				}
				elseif ($cache->size !== $file->size) {
					$cache_differs = true;
				}

				unset($cache_files[$file->path]);
			}

			if ($cache && !$cache_differs) {
				continue;
			}

			if ($cache) {
				$cache->loadFromEntity($file);
				$file = $cache;
			}
			else {
				$file->deleteCache();
			}

			// Don't index versioned files as trashed
			if ($file->context() === $file::CONTEXT_TRASH && strpos($file->path, $file::CONTEXT_TRASH . $file::CONTEXT_VERSIONS) === 0) {
				$file->set('trash', $file->modified);
			}

			// Re-create MD5 hash
			$file->rehash();

			// save() will *also* add the file to the users_files or transactions_files table
			$file->save();

			if ($callback) {
				$callback($cache ? 'update' : 'create', $file);
			}
		}

		unset($file, $cache);

		// Delete cached files that are not in backend storage from cache
		foreach ($cache_files as $file) {
			// Don't use ->delete() here as it would trigger delete from storage even if there was a bug
			// but we don't want to risk losing any data
			$file->deleteSafe();

			if ($callback) {
				$callback('delete_cache', $file);
			}
		}

		$db->commit();
	}

	/**
	 * Copy all files from a storage backend to another one
	 * This can be used to move from SQLite to FileSystem for example
	 * Note that this only copies files, and is not removing them from the source storage backend.
	 */
	static public function migrate(string $from, string $to, $from_config = null, $to_config = null, ?callable $callback = null): void
	{
		self::call($from, 'configure', $from_config);
		self::call($to, 'configure', $to_config);

		$db = DB::getInstance();

		try {
			if (self::call($from, 'isLocked')) {
				throw new \RuntimeException('Storage is locked: ' . $from);
			}

			if (self::call($to, 'isLocked')) {
				throw new \RuntimeException('Storage is locked: ' . $to);
			}

			self::call($from, 'lock');
			self::call($to, 'lock');

			$db->begin();
			$i = 0;

			foreach (Files::all() as $file) {
				if ($file->isDir()) {
					continue;
				}

				if (++$i >= 100) {
					$db->commit();
					$db->begin();
					$i = 0;
				}

				if (null !== $callback) {
					$callback('copy', $file);
				}

				if ($pointer = self::call($from, 'getReadOnlyPointer', $file)) {
					self::call($to, 'storePointer', $file, $pointer);
					fclose($pointer);
				}
				elseif (($path = self::call($from, 'getLocalFilePath', $file)) && file_exists($path)) {
					self::call($to, 'storePath', $file, $path);
				}
				else {
					$errors[] = sprintf('%s: no pointer or local file path found in "%s"', $file->path, $from);
				}
			}

			$db->commit();
		}
		catch (\RuntimeException $e) {
			throw new \RuntimeException('Migration failed', 0, $e);
		}
		finally {
			if ($db->inTransaction()) {
				$db->rollback();
			}

			self::call($from, 'unlock');
			self::call($to, 'unlock');
		}
	}

	/**
	 * Export files contents from local storage to an external database file.
	 * Use $quota to restrict the quota allowed for files contents
	 */
	static public function export(string $path, ?int $quota = null): void
	{
		$db = new \SQLite3($path);

		$tables_count = $db->querySingle('SELECT COUNT(*) FROM sqlite_master WHERE type = \'table\' AND name IN (\'files\', \'files_contents\');');

		if (2 !== $tables_count) {
			throw new \LogicException('The target database is not a valid Paheko database');
		}

		$db->exec('PRAGMA foreign_keys = ON; BEGIN; DELETE FROM files_contents;');
		$st = $db->prepare('INSERT OR REPLACE INTO files_contents (id, content) VALUES (?, zeroblob(?));');
		$total_size = 0;

		foreach (Files::all() as $file) {
			if ($file->isDir()) {
				continue;
			}

			if (null !== $quota
				&& $total_size + $file->size >= $quota) {
				continue;
			}

			$pointer = $file->getReadOnlyPointer();

			if (!$pointer) {
				$path = $file->getLocalFilePath();

				if (!$path || !file_exists($path)) {
					continue;
				}

				$pointer = fopen($path, 'rb');
			}

			$st->clear();
			$st->reset();
			$st->bindValue(1, $file->id());
			$st->bindValue(2, $file->size);
			$st->execute();

			$blob = $db->openBlob('files_contents', 'content', $file->id, 'main', \SQLITE3_OPEN_READWRITE);

			while (!feof($pointer)) {
				$bytes = fread($pointer, 8192);
				fwrite($blob, $bytes);
			}

			fclose($pointer);
			fclose($blob);
			$total_size += $file->size;
		}

		// Delete files that could not be copied because quota has been exceeded
		// don't delete directories or it will also delete files inside them (via foreign keys)
		$db->exec('DELETE FROM files WHERE type = 1 AND id NOT IN (SELECT id FROM files_contents);');

		$db->exec('COMMIT;');
		$db->close();

		// reopen to vacuum, if we just vacuum then we might get an error
		// because of the blob pointers, even though they should be closed?
		// https://stackoverflow.com/questions/41516542/sqlite-error-statements-in-progress-when-no-statements-should-be#comment127004699_41516542
		$db = new \SQLite3($path);
		$db->exec('VACUUM;');
		$db->close();
	}

	/**
	 * Delete all files from a storage backend
	 */
	static public function truncate(string $backend, $config = null): void
	{
		self::call($backend, 'configure', $config);
		self::call($backend, 'truncate');
	}

	/**
	 * Do cleanup
	 */
	static public function cleanup(): void
	{
		Files::callStorage('cleanup');
	}
}
