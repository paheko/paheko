<?php

namespace Garradin\Files\Storage;

use Garradin\Entities\Files\File;

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
			return file_put_contents($target, $content);
		}
	}

	static public function list(string $path): array
	{
		$path = self::_getRoot() . ($path ? DIRECTORY_SEPARATOR . $path : '') . DIRECTORY_SEPARATOR . '*';
		$files = glob($path);
		$list = [];

		foreach ($files as $file) {
		}

		return $list;
	}

	static public function getPath(File $file): ?string
	{
		if (null == $file->storage_path) {
			$file->storage_path = $file->path() . DIRECTORY_SEPARATOR . $file->name;
		}

		return self::_getRoot() . DIRECTORY_SEPARATOR . $file->storage_path;
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
