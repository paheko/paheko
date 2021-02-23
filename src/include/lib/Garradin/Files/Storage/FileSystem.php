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

		if (!is_writable($config) && !Utils::safe_mkdir($config)) {
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
		$target = self::getFullPath($file->path());
		self::ensureDirectoryExists(dirname($target));

		if (null !== $path) {
			return copy($path, $target);
		}
		else {
			return file_put_contents($target, $content) === false ? false : true;
		}
	}

	static public function list(string $path): array
	{
		$fullpath = self::_getRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
		$fullpath = rtrim($fullpath, DIRECTORY_SEPARATOR);

		if (!file_exists($fullpath)) {
			return [];
		}

		$files = [];

		foreach (new \FilesystemIterator($fullpath, \FilesystemIterator::SKIP_DOTS) as $file) {
			$data = [
				'path'     => $path,
				'name'     => $file->getFilename(),
				'modified' => null,
				'size'     => null,
			];

			if ($file->isDir()) {
				$data['type'] = File::TYPE_DIRECTORY;
			}
			else {
				$data['modified'] = $file->getMTime();
				$data['size'] = $file->getSize();
				$data['type'] = mime_content_type($file->getRealpath());
			}

			// Used to make sorting easier
			// directory_blabla
			// file_image.jpeg
			$files[$file->getType() . '_' .$file->getFilename()] = $data;
		}

		uksort($files, function ($a, $b) {
			return strnatcasecmp($a, $b) > 0 ? 1 : -1;
		});

		return $files;
	}

	static public function getFullPath(File $file): ?string
	{
		return self::_getRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->pathname());
	}

	static public function display(File $file): void
	{
		readfile(self::getFullPath($file));
	}

	static public function fetch(File $file): string
	{
		return file_get_contents(self::getFullPath($file));
	}

	static public function delete(string $path): bool
	{
		$path = self::getFullPath($path);

		if (is_dir($path)) {
			return rmdir($path);
		}
		else {
			return unlink($path);
		}
	}

	static public function move(File $file, string $new_path): bool
	{
		$target = self::getFullPath($new_path);
		self::ensureDirectoryExists(dirname($target));

		return rename(self::getFullPath($old_path), $target);
	}

	static public function exists(string $path): bool
	{
		return (bool) file_exists(self::getFullPath($path));
	}

	static public function modified(string $path): ?int
	{
		return filemtime(self::getFullPath($path)) ?: null;
	}

	static public function size(string $path): ?int
	{
		return filesize(self::getFullPath($path)) ?: null;
	}

	static public function stat(string $path): ?array
	{
		$fullpath = self::getFullPath($path);

		if (!file_exists($fullpath)) {
			return null;
		}

		$file = new \SplFileInfo($fullpath);

		return [
			'modified' => $file->getMTime(),
			'size'     => $file->getSize(),
			'type'     => $file->isDir() ? File::TYPE_DIRECTORY : mime_content_type($file->getRealPath()),
			'path'     => dirname($path),
			'name'     => basename($path),
		];
	}

	static public function mkdir(string $path): bool
	{
		return Utils::safe_mkdir(self::getFullPath($path));
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

	static public function sync(?string $path): void
	{
		$fullpath = self::_getRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
		$fullpath = rtrim($fullpath, DIRECTORY_SEPARATOR);

		if (!file_exists($fullpath)) {
			return;
		}

		$db = DB::getInstance();

		$saved_files = $db->getGrouped('SELECT name, size, modified, type FROM files WHERE path = ?;', $path);
		$added = [];
		$deleted = [];
		$modified = [];
		$exists = [];

		foreach (new \FilesystemIterator($fullpath, \FilesystemIterator::SKIP_DOTS) as $file) {
			$name = $file->getFilename();

			$data = [
				'path'     => $path,
				'name'     => $name,
				'modified' => null,
				'size'     => null,
			];

			if ($file->isDir()) {
				$data['type'] = File::TYPE_DIRECTORY;
			}
			else {
				$data['type'] = File::TYPE_FILE;
				$data['modified'] = $file->getMTime();
				$data['size'] = $file->getSize();
				$data['mime'] = mime_content_type($file->getRealpath());
			}

			$exists[$name] = null;

			if (!array_key_exists($name, $saved_files)) {
				$added[] = $data;
			}
			elseif (($saved_files[$name]->size != $data['size'] || $saved_files[$name]->modified != $data['modified'])) {
				$modified[] = $data;
			}
		}

		foreach ($modified as $file) {
			Files::update($file['path'], $file['name'])
		}

		$deleted = array_diff_key($saved_files, $exists);

		foreach ($deleted as $file) {
			$type = $saved_files[$file['name']]->type;

			if ($type == File::TYPE_DIRECTORY) {
				$sql = 'DELETE FROM files WHERE '
			}
		}
	}

	static public function update(File $file): File
	{
		$modified = filemtime(self::getFullPath($file->pathname()));

		if ($modified != $file->modified->getTimestamp()) {
			$file->modified = new \DateTime('@' . $modified);
		}
	}
}
