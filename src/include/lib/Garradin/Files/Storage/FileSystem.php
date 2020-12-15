<?php

namespace Garradin\Files\Storage;

use Garradin\Entities\Files\File;

use const Garradin\FILE_STORAGE_CONFIG;

/**
 * This class provides storage in the file system
 * You need ton configure FILE_STORAGE_CONFIG to give a file path
 */
class FileSystem implements StorageInterface
{
	static protected $_size;

	static protected function _getRoot()
	{
		if (!FILE_STORAGE_CONFIG) {
			throw new \RuntimeException('Le stockage de fichier n\'a pas été configuré (FILE_STORAGE_CONFIG est vide).');
		}

		if (!is_writable(FILE_STORAGE_CONFIG)) {
			throw new \RuntimeException('Le répertoire de stockage des fichiers est protégé contre l\'écriture.');
		}

		$target = rtrim(FILE_STORAGE_CONFIG, DIRECTORY_SEPARATOR);
		return realpath($target);
	}

	static protected function ensureDirectoryExists(string $path): void
	{
		if (is_dir($path)) {
			return;
		}

		$permissions = fileperms(self::_getRoot(null));

		mkdir($path, $permissions & 0777, true);
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

	static public function list(?string $path): ?array
	{
		$path = self::_getRoot() . ($path ? DIRECTORY_SEPARATOR . $file->path : '') . DIRECTORY_SEPARATOR . '*';
		$files = glob($path);
		$list = [];

		foreach ($files as $file) {
		}

		return $list;
	}

	static public function getPath(File $file): ?string
	{
		$path = '';

		if ($file->path) {
			$path .= DIRECTORY_SEPARATOR . $file->path;
		}

		$path .= DIRECTORY_SEPARATOR . $file->name;

		return self::_getRoot() . $path;
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

	static public function getTotalSize(): ?int
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

	static public function cleanup(): void
	{
	}
}
