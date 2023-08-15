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
	static public function sync(?string $path = null): void
	{
		if (FILE_STORAGE_BACKEND == 'SQLite') {
			return;
		}

		$db = DB::getInstance();
		$db->begin();

		$cache_files = Files::list($path);
		$local_files = Files::callStorage('listFiles', $path);

		foreach ($local_files as $file) {
			if ($file->type == $file::TYPE_DIRECTORY) {
				self::sync($file->path);
				continue;
			}

			$cache_found = false;
			$cache_differs = false;

			foreach ($cache_files as $key => $cache) {
				if ($file->name !== $cache->name) {
					continue;
				}

				$cache_found = true;
				$cache_differs = $cache->modified->getTimestamp() !== $file->modified->getTimestamp() || $cache->size !== $file->size;
				unset($cache_files[$key]);
				break;
			}

			if ($cache_found && !$cache_differs) {
				continue;
			}

			if ($cache_found) {
				// Replace cache file with local file
				$cache->import($file->asArray(true));
				$cache->exists(true);
				$file = $cache;
			}

			if ($file->context() === $file::CONTEXT_TRASH) {
				$file->set('trash', $file->modified);
			}

			$file->deleteCache();

			// Re-create MD5 hash
			$file->rehash();

			// save() will *also* add the file to the users_files or transactions_files table
			$file->save();
		}

		unset($file, $cache);

		// Remove directories
		$cache_files = array_filter($cache_files, fn($file) => !$file->isDir());

		// Delete cached files that are not in backend storage from cache
		if (count($cache_files)) {
			foreach ($cache_files as $file) {
				// Don't use ->delete() here as it would trigger delete from storage even if there was a bug
				// but we don't want to risk losing any data
				$file->deleteSafe();
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

		}
		catch (RuntimeException $e) {
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
