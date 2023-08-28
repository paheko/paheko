<?php

namespace Paheko\Files\Storage;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\DB;
use Paheko\Utils;
use Paheko\ValidationException;

use const Paheko\DATA_ROOT;

/**
 * This class stores files in the local file system.
 * You need to configure FILE_STORAGE_CONFIG to give a root path.
 * Metadata will be stored and cached in the 'files' table.
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

		self::$_root = rtrim($config, DIRECTORY_SEPARATOR);
	}

	static protected function _getRoot()
	{
		if (!self::$_root) {
			throw new \RuntimeException('Le stockage de fichier n\'a pas été configuré (FILE_STORAGE_CONFIG est vide ?).');
		}

		$root = rtrim(self::$_root, DIRECTORY_SEPARATOR);

		if (!file_exists($root)) {
			Utils::safe_mkdir($root, 0770, true);
		}

		return $root;
	}

	static protected function ensureParentDirectoryExists(string $path): void
	{
		$path = Utils::dirname($path);

		if (is_dir($path)) {
			return;
		}

		$permissions = fileperms(self::_getRoot());

		Utils::safe_mkdir($path, $permissions & 0777, true);
	}

	static public function storePath(File $file, string $source_path): bool
	{
		$target = self::getLocalFilePath($file);
		self::ensureParentDirectoryExists($target);

		$tmpfile = tempnam(CACHE_ROOT, 'file-');
		$return = copy($source_path, $tmpfile);

		if ($return) {
			rename($tmpfile, $target);
			touch($target, $file->modified->getTimestamp());
		}

		return $return;
	}

	static public function storeContent(File $file, string $source_content): bool
	{
		$target = self::getLocalFilePath($file);
		self::ensureParentDirectoryExists($target);

		$tmpfile = tempnam(CACHE_ROOT, 'file-');
		$return = file_put_contents($tmpfile, $source_content) === false ? false : true;

		if ($return) {
			rename($tmpfile, $target);
			touch($target, $file->modified->getTimestamp());
		}

		return $return;
	}

	static public function storePointer(File $file, $pointer): bool
	{
		$target = self::getLocalFilePath($file);
		self::ensureParentDirectoryExists($target);

		$tmpfile = tempnam(CACHE_ROOT, 'file-');
		$fp = fopen($tmpfile, 'w');

		while (!feof($pointer)) {
			fwrite($fp, fread($pointer, 8192));
		}

		fclose($fp);

		rename($tmpfile, $target);
		touch($target, $file->modified->getTimestamp());

		return true;
	}

	static public function getLocalFilePath(File $file): ?string
	{
		return self::_getStoragePath($file->path);
	}

	static protected function _getStoragePath(string $path): string
	{
		$path = self::_getRoot() . '/' . $path;
		return str_replace('/', DIRECTORY_SEPARATOR, $path);
	}

	static public function getReadOnlyPointer(File $file)
	{
		try {
			return fopen(self::getLocalFilePath($file), 'rb');
		}
		catch (\Throwable $e) {
			if (false !== strpos($e->getMessage(), 'No such file')) {
				return null;
			}

			throw $e;
		}
	}

	static public function touch(File $file, \DateTime $date): void
	{
		$path = self::getLocalFilePath($file);
		touch($path, $date->getTimestamp());
	}

	static public function rename(File $file, string $new_path): bool
	{
		$path = self::getLocalFilePath($file);
		$new_path = self::_getStoragePath($new_path);

		if (!file_exists($path)) {
			return true;
		}

		// Overwrite
		if (file_exists($new_path)) {
			Utils::safe_unlink($new_path, true);
		}

		self::ensureParentDirectoryExists($new_path);

		rename($path, $new_path);
		return true;
	}

	static public function delete(File $file): bool
	{
		$path = self::getLocalFilePath($file);

		if (!file_exists($path)) {
			return true;
		}

		Utils::deleteRecursive($path, true);
		return true;
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

	static protected function _SplToFile(\SplFileInfo $spl): ?File
	{
		// may return slash
		// see comments https://www.php.net/manual/fr/splfileinfo.getfilename.php
		// don't use getBasename as it is locale-dependent!
		$name = trim($spl->getFilename(), '/');
		$root = self::_getRoot();

		$path = substr($spl->getPathname(), strlen($root . DIRECTORY_SEPARATOR));
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

		try {
			File::validateFileName($name);
			File::validatePath($path);
		}
		catch (ValidationException $e) {
			// Invalid files paths or names cannot be added to file cache
			return null;
		}

		$parent = Utils::dirname($path);

		if ($parent == '.' || !$parent) {
			$parent = null;
		}

		$data = [
			'id'       => null,
			'name'     => $name,
			'path'     => $path,
			'parent'   => $parent,
			'modified' => new \DateTime('@' . $spl->getMTime()),
			'size'     => $spl->isDir() ? null : $spl->getSize(),
			'type'     => $spl->isDir() ? File::TYPE_DIRECTORY : File::TYPE_FILE,
			'mime'     => $spl->isDir() ? null : mime_content_type($spl->getRealPath()),
			'md5'      => null,
			'trash'    => null,
		];

		$data['modified']->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
		$data['image'] = (int) in_array($data['mime'], File::IMAGE_TYPES);

		$file = new File;
		$file->load($data);

		return $file;
	}

	static public function listFiles(?string $path = null): array
	{
		$root = self::_getRoot();
		$fullpath = $root . DIRECTORY_SEPARATOR . $path;

		if (!file_exists($fullpath)) {
			return [];
		}

		$files = [];

		foreach (new \FilesystemIterator($fullpath, \FilesystemIterator::SKIP_DOTS) as $file) {
			// Seems that SKIP_DOTS does not work all the time?
			if ($file->getFilename()[0] == '.') {
				continue;
			}

			$obj = self::_SplToFile($file);

			// Skip invalid files
			if (null === $obj) {
				continue;
			}

			// Used to make sorting easier
			// directory_blabla
			// file_image.jpeg
			$files[$file->getFilename()] = $obj;
		}

		return $files;
	}

	static public function cleanup(): void
	{
		self::_cleanupDirectory(null);
	}

	/**
	 * Delete empty directories
	 */
	static protected function _cleanupDirectory(?string $path): void
	{
		$path ??= self::_getRoot();;

		foreach (glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
			self::_cleanupDirectory($dir);
			@rmdir($dir);
		}
	}
}
