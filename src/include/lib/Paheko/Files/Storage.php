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
	 * Only used to migrate from Paheko < 1.3 where storage was on local filesystem
	 * @deprecated
	 */
	static public function legacySync(string $root): void
	{
		$root = rtrim(strtok($root, '%'), '/');

		if (!is_dir($root)) {
			return;
		}

		// Move skeletons to new path
		if (file_exists($root . '/skel')) {
			if (!file_exists($root . '/modules')) {
				Utils::safe_mkdir($root . '/modules', 0777, true);
			}

			if (!file_exists($root . '/modules/web')) {
				rename($root . '/skel', $root . '/modules/web');
			}
		}

		if (!is_dir($root . '.old')) {
			rename($root, $root . '.old');
		}

		Utils::deleteRecursive($root);
		Utils::safe_mkdir($root, 0777, true);

		self::legacySyncDirectory($root . '.old');
	}

	static protected function legacySyncDirectory(string $root, ?string $path = null)
	{
		foreach (self::listLocalFiles($root, $path) as $file) {
			if ($file->isDir()) {
				self::legacySyncDirectory($root, $file->path);
			}
			else {
				$file->store(['path' => $root . DIRECTORY_SEPARATOR . $file->path]);
			}
		}
	}

	static protected function _SplToFile(string $root, \SplFileInfo $spl): File
	{
		$path = str_replace($root . DIRECTORY_SEPARATOR, '', $spl->getPathname());
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		$parent = Utils::dirname($path);

		if ($parent == '.' || !$parent) {
			$parent = null;
		}

		$data = [
			'id'       => null,
			// may return slash
			// see comments https://www.php.net/manual/fr/splfileinfo.getfilename.php
			// don't use getBasename as it is locale-dependent!
			'name'     => trim($spl->getFilename(), '/'),
			'path'     => $path,
			'parent'   => $parent,
			'modified' => new \DateTime('@' . $spl->getMTime()),
			'size'     => $spl->isDir() ? null : $spl->getSize(),
			'type'     => $spl->isDir() ? File::TYPE_DIRECTORY : File::TYPE_FILE,
			'mime'     => $spl->isDir() ? null : mime_content_type($spl->getRealPath()),
			'md5'      => $spl->isDir() ? null : md5_file($spl->getPathname()),
			'trash'    => 0 === strpos($path, 'trash/') ? new \DateTime : null,
		];

		$data['modified']->setTimeZone(new \DateTimeZone(date_default_timezone_get()));

		$data['image'] = (int) in_array($data['mime'], File::IMAGE_TYPES);

		$file = new File;
		$file->load($data);

		return $file;
	}

	static protected function listLocalFiles(string $root, ?string $path = null): array
	{
		$fullpath = $root;

		if ($path) {
			$fullpath .= DIRECTORY_SEPARATOR . $path;
		}

		$files = [];

		foreach (new \FilesystemIterator($fullpath, \FilesystemIterator::SKIP_DOTS) as $file) {
			// Seems that SKIP_DOTS does not work all the time?
			if ($file->getFilename()[0] == '.') {
				continue;
			}

			// Used to make sorting easier
			// directory_blabla
			// file_image.jpeg
			$files[$file->getType() . '_' .$file->getFilename()] = self::_SplToFile($root, $file);
		}

		return Utils::knatcasesort($files);
	}

	/**
	 * Used to sync files between a local directory and Paheko storage
	 * Plase note that the sync algorithm is currently very limited
	 * @todo FIXME use csync algo:
	 * @see https://csync.org/userguide/#_file_synchronization
	 */
	static protected function sync(string $root, ?string $path = null, ?SQLite3 $db = null)
	{
		$db_files = Files::list($path);
		$local_files = self::listLocalFiles($root, $path);

		/*
		if (null === $path) {
			$db_path = $root . DIRECTORY_SEPARATOR .  '.pahekosync.db';
			$db_exists = file_exists($db_path);
			$db = new SQLite3($db_path);

			if (!$db_exists) {
				$db->exec('CREATE TABLE IF NOT EXISTS metadata (
						path TEXT NOT NULL PRIMARY KEY,
						parent TEXT NULL REFERENCES metadata (path) ON DELETE CASCADE,
						modified INTEGER NOT NULL,
						size INTEGER NOT NULL,
						inode INTEGER NOT NULL
					);
					CREATE UNIQUE INDEX inode ON metadata (inode);');

				$indexFiles = function (string $path) use ($db) {

				};

				$indexFiles($root);
			}
		}
		*/

		foreach ($local_files as $file) {
			if ($file->type == $file::TYPE_DIRECTORY) {
				self::sync($root, $file->path);
				continue;
			}

			$local_found = false;
			$local_differs = false;

			foreach ($local_files as $key => $local) {
				if ($file->name != $local->name) {
					continue;
				}

				$local_found = true;
				$local_differs = $local->modified !== $file->modified || $local->size !== $file->size;
				unset($local_found[$key]);
				break;
			}

			if ($local_found && !$local_differs) {
				continue;
			}

			if ($local_differs) {
				$file->id($local->id());
				$file->deleteCache();
			}

			$file->store(['path' => $root . DIRECTORY_SEPARATOR . $file->path]);
		}

		// Delete local files that are not in backend storage
		foreach ($local_files as $file) {
			$file->delete();
		}
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
}
