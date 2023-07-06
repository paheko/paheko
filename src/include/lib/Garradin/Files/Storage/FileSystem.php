<?php

namespace Garradin\Files\Storage;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\DB;
use Garradin\Utils;

use const Garradin\DATA_ROOT;

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

		$permissions = fileperms(self::_getRoot());

		Utils::safe_mkdir($path, $permissions & 0777, true);
	}

	static public function storePath(File $file, string $source_path): bool
	{
		$target = self::getLocalFilePath($file);
		self::ensureDirectoryExists(dirname($target));

		$return = copy($source_path, $target);

		if ($return) {
			touch($target, $file->modified->getTimestamp());
		}

		return $return;
	}

	static public function storeContent(File $file, string $source_content): bool
	{
		$target = self::getLocalFilePath($file);
		self::ensureDirectoryExists(dirname($target));

		$return = file_put_contents($target, $source_content) === false ? false : true;

		if ($return) {
			touch($target, $file->modified->getTimestamp());
		}

		return $return;
	}

	static public function storePointer(File $file, $pointer): bool
	{
		$target = self::getLocalFilePath($file);
		self::ensureDirectoryExists(dirname($target));

		$fp = fopen($target, 'w');

		while (!feof($pointer)) {
			fwrite($fp, fread($pointer, 8192));
		}

		fclose($fp);

		touch($target, $file->modified->getTimestamp());

		return true;
	}

	static public function mkdir(File $file): bool
	{
		return Utils::safe_mkdir(self::getLocalFilePath($file), 0777, true);
	}

	static public function touch(string $path, \DateTimeInterface $date = null): bool
	{
		$date = $date ? $date->getTimestamp() : time();
		return touch(self::_getRealPath($path), $date);
	}

	static protected function _getRealPath(string $path): ?string
	{
		if (substr(trim($path, '/'), 0, 1) == '.') {
			return null;
		}

		return self::_getRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
	}

	static public function getLocalFilePath(File $file): ?string
	{
		return self::_getRealPath($file->path);
	}

	static public function getReadOnlyPointer(File $file)
	{
		return fopen(self::getLocalFilePath($file), 'rb');
	}

	static public function display(File $file): void
	{
		readfile(self::getLocalFilePath($file));
	}

	static public function fetch(File $file): string
	{
		return file_get_contents(self::getLocalFilePath($file));
	}

	static public function delete(File $file): bool
	{
		$path = self::getLocalFilePath($file);

		if ($file->type == File::TYPE_DIRECTORY) {
			return Utils::deleteRecursive($path, true);
		}

		return Utils::safe_unlink($path);
	}

	static public function move(File $file, string $new_path): bool
	{
		$source = self::getLocalFilePath($file);
		$target = self::_getRealPath($new_path);

		self::ensureDirectoryExists(dirname($target));

		return rename($source, $target);
	}

	static public function exists(string $path): bool
	{
		return file_exists(self::_getRealPath($path));
	}

	static protected function _SplToFile(\SplFileInfo $spl): File
	{
		$path = str_replace(self::_getRoot() . DIRECTORY_SEPARATOR, '', $spl->getPathname());
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		$parent = Utils::dirname($path);

		if ($parent == '.' || !$parent) {
			$parent = '';
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
			'size'     => $spl->getSize(),
			'type'     => $spl->isDir() ? File::TYPE_DIRECTORY : File::TYPE_FILE,
			'mime'     => mime_content_type($spl->getRealPath()),
			'md5'      => null,
			'trash'    => 0 === strpos($path, 'trash/') ? new \DateTime : null,
		];

		$data['modified']->setTimeZone(new \DateTimeZone(date_default_timezone_get()));

		$data['image'] = (int) in_array($data['mime'], File::IMAGE_TYPES);

		$file = new File;
		$file->load($data);
		$file->parent = $parent; // Force empty parent to be empty, not null

		return $file;
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
			// Seems that SKIP_DOTS does not work all the time?
			if ($file->getFilename()[0] == '.') {
				continue;
			}

			// Used to make sorting easier
			// directory_blabla
			// file_image.jpeg
			$files[$file->getType() . '_' .$file->getFilename()] = self::_SplToFile($file);
		}

		return Utils::knatcasesort($files);
	}

	static protected function _recurseGlob(string $path, string $pattern = '*', int $flags = 0): array
	{
		$target = $path . DIRECTORY_SEPARATOR . $pattern;
		$list = [];

		// glob is the fastest way to recursely list directories and files apparently
		// after comparing with opendir(), dir() and filesystem recursive iterators
		foreach(glob($target, $flags) as $file) {
			$file = basename($file);

			if ($file[0] == '.') {
				continue;
			}

			$list[] = $file;

			if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
				foreach (self::_recurseGlob($path . DIRECTORY_SEPARATOR . $file, $pattern, $flags) as $subfile) {
					$list[] = $file . DIRECTORY_SEPARATOR . $subfile;
				}
			}
		}

		return $list;
	}

	/**
	 * @see https://www.crazyws.fr/dev/fonctions-php/fonction-disk-free-space-et-disk-total-space-pour-ovh-2JMH9.html
	 * @see https://github.com/jdel/sspks/commit/a890e347f32e9e3e50a0dd82398947633872bf38
	 */
	static public function getQuota(): float
	{
		$quota = disk_total_space(self::_getRoot());
		return $quota === false ? (float) \PHP_INT_MAX : (float) $quota;
	}

	static public function getRemainingQuota(): float
	{
		$quota = @disk_free_space(self::_getRoot());
		return $quota === false ? (float) \PHP_INT_MAX : (float) $quota;
	}

	static public function truncate(): void
	{
		Utils::deleteRecursive(self::_getRoot(), false);
	}

	static public function lock(): void
	{
		touch(self::_getRoot() . DIRECTORY_SEPARATOR . '.lock');
	}

	static public function unlock(): void
	{
		Utils::safe_unlink(self::_getRoot() . DIRECTORY_SEPARATOR . '.lock');
	}

	static public function isLocked(): bool
	{
		return file_exists(self::_getRoot() . DIRECTORY_SEPARATOR . '.lock');
	}
}
