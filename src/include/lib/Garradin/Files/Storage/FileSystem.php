<?php

namespace Garradin\Files\Storage;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Utils;

use const Garradin\FILE_STORAGE_CONFIG;

/**
 * This class provides storage in the file system
 * You need to configure FILE_STORAGE_CONFIG to give a file path
 */
class FileSystem implements StorageInterface
{
	static protected $_size;
	static protected $_root;

	static public function configure(?string $config): void
	{
		if (!$config) {
			throw new \RuntimeException('Le stockage de fichier n\'a pas été configuré (FILE_STORAGE_CONFIG est vide).');
		}

		if (!is_writable($config)) {
			throw new \RuntimeException('Le répertoire de stockage des fichiers est protégé contre l\'écriture.');
		}

		$target = rtrim($config, DIRECTORY_SEPARATOR);
		self::$_root = realpath($target);
	}

	static protected function _getRoot()
	{
		if (!self::$_root) {
			throw new \RuntimeException('Le stockage de fichier n\'a pas été configuré (FILE_STORAGE_CONFIG est vide ?).');
		}

		return self::$_root;
	}

	static protected function ensureDirectoryExists(string $path): void
	{
		if (is_dir($path)) {
			return;
		}

		$permissions = fileperms(self::_getRoot(null));

		Utils::safe_mkdir($path, $permissions & 0777, true);
	}

	static public function store(File $file, ?string $path, ?string $content): bool
	{
		$target = self::getPath($file);
		self::ensureDirectoryExists(dirname($target));

		if (null !== $path) {
			return copy($path, $target);
		}
		else {
			return file_put_contents($target, $content) === false ? false : true;
		}
	}

	static public function list(string $context, ?string $context_ref): array
	{
		$path = File::getPath($context, $context_ref);
		$path = self::_getRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

		$directories = $files = [];

		foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $file) {
			if ($file->isDir()) {
				$directories[] = $file->getFilename();
				continue;
			}

			$relative_path = str_replace(self::_getRoot() . DIRECTORY_SEPARATOR, '', $file->getPathname());
			$relative_path = str_replace(DIRECTORY_SEPARATOR, '/', $relative_path);

			$file_object = Files::getFromPath($relative_path);

			if (!$file_object) {
				$file_object = File::createFromExisting($relative_path, self::_getRoot(), $file);
			}
			else {
				$file_object->updateIfNeeded($file);
			}

			$files[] = $file_object;
		}

		usort($files, function ($a, $b) {
			return strnatcasecmp($a->name, $b->name) > 0 ? 1 : -1;
		});

		return $directories + $files;
	}

	static public function getPath(File $file): ?string
	{
		return self::_getRoot() . DIRECTORY_SEPARATOR . $file->path();
	}

	static public function display(File $file): void
	{
		readfile(self::getPath($file));
	}

	static public function fetch(File $file): string
	{
		return file_get_contents(self::getPath($file));
	}

	static public function delete(File $file): bool
	{
		return unlink(self::getPath($file));
	}

	static public function move(File $old_file, File $new_file): bool
	{
		$target = self::getPath($new_file);
		self::ensureDirectoryExists(dirname($target));

		return rename(self::getPath($old_file), $target);
	}

	static public function exists(string $context, ?string $context_ref, string $name): bool
	{
		return (bool) file_exists(File::getPath($context, $context_ref, $name));
	}

	static public function modified(File $file): ?int
	{
		return filemtime(self::getPath($file)) ?: null;
	}

	static public function hash(File $file): ?string
	{
		return sha1_file(self::getPath($file));
	}

	static public function size(File $file): ?int
	{
		return filesize(self::getPath($file)) ?: null;
	}

	static public function getTotalSize(): int
	{
		if (null !== self::$_size) {
			return self::$_size;
		}

		$total = 0;

		$path = self::_getRoot();

		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $p) {
			$total += $p->getSize();
		}

		self::$_size = (int) $total;

		return self::$_size;
	}

	static public function getQuota(): int
	{
		return disk_total_space(self::_getRoot());
	}

	static public function sync(): void
	{
		// FIXME
	}

/*
	static public function sync(): void
	{
		$db = DB::getInstance();

		self::lock();

		$db->exec('CREATE TEMP TABLE tmp_files (path, name, modified, size); BEGIN;');
		$insert = $db->prepare('INSERT INTO tmp_files_paths VALUES (?, ?, ?, ?);');
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
		$root = self::_getRoot();

		foreach ($iterator as $f) {
			$path = str_replace($root, '', $f->getPath());
			$insert->bindValue('1', $path);
			$insert->bindValue('2', $f->getName());
			$insert->bindValue('3', $f->getMTime());
			$insert->bindValue('4', $f->getSize());
			$insert->execute();
			$insert->reset();
		}

		$db->exec('COMMIT; BEGIN;');

		// Process files missing in DB
		$res = $db->iterate('SELECT t.path, t.name FROM tmp_files_paths t INNER JOIN files f ON f.path = t.path AND f.name = t.name WHERE f.id IS NULL;');

		foreach ($res as $row) {
			File::createFromExisting($row->path . '/' . $row->name, $root);
		}

		$db->exec('COMMIT; BEGIN;');

		// Delete local files that have been removed from database
		$res = EM::getInstance(File::class)->iterate('SELECT f.* FROM files f INNER JOIN tmp_files_paths t ON f.path = t.path AND f.name = t.name WHERE t.path IS NULL;');

		foreach ($res as $file) {
			$file->delete();
		}

		$db->exec('COMMIT; DROP TABLE tmp_files_paths;');

		self::unlock();
	}
*/

	static public function reset(): void
	{
		Utils::deleteRecursive(self::_getRoot());
	}

	static public function lock(): void
	{
		touch(self::_getRoot() . DIRECTORY_SEPARATOR . '.lock');
	}

	static public function unlock(): void
	{
		Utils::safe_unlink(self::_getRoot() . DIRECTORY_SEPARATOR . '.lock');
	}

	static public function checkLock(): void
	{
		$lock = file_exists(self::_getRoot() . DIRECTORY_SEPARATOR . '.lock');

		if ($lock) {
			throw new \RuntimeException('File storage is locked');
		}
	}
}
