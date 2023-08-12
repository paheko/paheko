<?php

namespace Paheko\Files\Storage;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\DB;
use Paheko\Utils;

use const Paheko\DATA_ROOT;

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

		self::$_root = rtrim($config, DIRECTORY_SEPARATOR);
	}

	static protected function _getRoot()
	{
		if (!self::$_root) {
			throw new \RuntimeException('Le stockage de fichier n\'a pas été configuré (FILE_STORAGE_CONFIG est vide ?).');
		}

		$root = rtrim(strtok(self::$_root, '%'), DIRECTORY_SEPARATOR);

		if (!file_exists($root)) {
			Utils::safe_mkdir($root, 0770, true);
		}

		return $root;
	}

	static protected function ensureDirectoryExists(string $path): void
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
		self::ensureDirectoryExists($target);

		$return = copy($source_path, $target);

		if ($return) {
			touch($target, $file->modified->getTimestamp());
		}

		return $return;
	}

	static public function storeContent(File $file, string $source_content): bool
	{
		$target = self::getLocalFilePath($file);
		self::ensureDirectoryExists($target);

		$return = file_put_contents($target, $source_content) === false ? false : true;

		if ($return) {
			touch($target, $file->modified->getTimestamp());
		}

		return $return;
	}

	static public function storePointer(File $file, $pointer): bool
	{
		$target = self::getLocalFilePath($file);
		self::ensureDirectoryExists($target);

		$fp = fopen($target, 'w');

		while (!feof($pointer)) {
			fwrite($fp, fread($pointer, 8192));
		}

		fclose($fp);

		touch($target, $file->modified->getTimestamp());

		return true;
	}

	static public function getLocalFilePath(File $file): ?string
	{
		$path = self::$_root . '/' . $file->path;
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

	/**
	 * @see https://specifications.freedesktop.org/trash-spec/trashspec-latest.html
	 */
	static public function trash(File $file): bool
	{
		$hash = md5($file->path);
		$src = self::getLocalFilePath($file);

		$target = self::$_root . '/.Trash/files/' . $hash;
		$info_file = self::$_root . '/.Trash/info/' . $hash;

		self::ensureDirectoryExists($target);
		self::ensureDirectoryExists($info_file);

		return rename($src, $target) &&
			// Write info file
			file_put_contents($info_file, sprintf("[Trash Info]\nPath=%s\nDeletionDate=%s\n", $file->path, date('Ymd\TH:i:s')));
	}

	static public function restore(File $file): bool
	{
		$hash = md5($file->path);

		$src = self::$_root . '/.Trash/files/' . $hash;
		$info_file = self::$_root . '/.Trash/info/' . $hash;

		$target = self::getLocalFilePath($file);

		if (!file_exists($src)) {
			return false;
		}

		return rename($src, $target) && Utils::safe_unlink($info_file);
	}

	static public function delete(File $file): bool
	{
		if ($file->trash) {
			$hash = md5($file->path);
			$src = self::$_root . '/.Trash/files/' . $hash;
			$info_file = self::$_root . '/.Trash/info/' . $hash;

			Utils::safe_unlink($src);
			Utils::safe_unlink($info_file);
		}
		else {
			$path = self::getLocalFilePath($file);
			return Utils::safe_unlink($path);
		}
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
