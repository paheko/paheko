<?php

namespace Garradin\Files\Storage;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\DB;
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

		$permissions = fileperms(self::_getRoot());

		Utils::safe_mkdir($path, $permissions & 0777, true);
	}

	static public function storePath(File $file, string $source_path): bool
	{
		$target = self::getFullPath($file);
		self::ensureDirectoryExists(dirname($target));

		$return = copy($source_path, $target);

		if ($return) {
			touch($target, $file->modified->getTimestamp());
		}

		return $return;
	}

	static public function storeContent(File $file, string $source_content): bool
	{
		$target = self::getFullPath($file);
		self::ensureDirectoryExists(dirname($target));

		$return = file_put_contents($target, $source_content) === false ? false : true;

		if ($return) {
			touch($target, $file->modified->getTimestamp());
		}

		return $return;
	}

	static public function mkdir(File $file): bool
	{
		return Utils::safe_mkdir(self::getFullPath($file));
	}

	static protected function _getRealPath(string $path)
	{
		return self::_getRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
	}

	static public function getFullPath(File $file): ?string
	{
		return self::_getRealPath($file->path);
	}

	static public function display(File $file): void
	{
		readfile(self::getFullPath($file));
	}

	static public function fetch(File $file): string
	{
		return file_get_contents(self::getFullPath($file));
	}

	static public function delete(File $file): bool
	{
		$path = self::getFullPath($file);

		if (is_dir($path)) {
			return rmdir($path);
		}
		else {
			return unlink($path);
		}
	}

	static public function move(File $file, string $new_path): bool
	{
		$source = self::getFullPath($file);
		$target = self::_getRealPath($new_path);

		self::ensureDirectoryExists(dirname($target));

		return rename($source, $target);
	}

	static public function exists(string $path): bool
	{
		return file_exists(self::_getRealPath($path));
	}

	static public function modified(File $file): ?int
	{
		return filemtime(self::getFullPath($file)) ?: null;
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
		$quota = @disk_total_space(self::_getRoot());
		return $quota === false ? \PHP_INT_MAX : (int) $quota;
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

		$saved_files = $db->getGrouped('SELECT name, size, modified, type FROM files WHERE parent = ?;', $path);
		$added = [];
		$modified = [];
		$exists = [];

		foreach (new \FilesystemIterator($fullpath, \FilesystemIterator::SKIP_DOTS) as $file) {
			$name = $file->getFilename();

			$data = [
				'name'     => $name,
				'modified' => null,
			];

			if ($file->isDir()) {
				$data['type'] = File::TYPE_DIRECTORY;
			}
			else {
				$data['type'] = File::TYPE_FILE;
				$data['modified'] = date('Y-m-d H:i:s', $file->getMTime());
			}

			$exists[$name] = true;

			if (!array_key_exists($name, $saved_files)) {
				$added[] = $data;
			}
			elseif ($saved_files[$name]->modified < $data['modified']) {
				$modified[] = $data;
			}
		}

		foreach ($modified as $file) {
			// This will call 'update' method
			Files::get($path . '/' . $file['name']);
		}

		foreach ($added as $file) {
			$f = File::create($path, $file['name'], $fullpath . DIRECTORY_SEPARATOR . $file['name'], null);
			$f->import($file);
			$f->save();
		}

		$deleted = array_diff_key($saved_files, $exists);

		foreach ($deleted as $file) {
			if ($file->type == File::TYPE_DIRECTORY) {

				$sql = 'DELETE FROM files WHERE path = ? OR path LIKE ? OR (path = ? AND name = ?);';
				$file_path = $path . '/' . $file->name;
				$params = [$file_path, $file_path . '/%', $path, $file->name];
			}
			else {
				$sql = 'DELETE FROM files WHERE path = ? AND name = ?;';
				$params = [$path, $file->name];
			}

			$db->preparedQuery($sql, ... $params);
		}
	}

	static public function update(File $file): ?File
	{
		$path = self::getFullPath($file);

		// File has disappeared
		if (!file_exists($path)) {
			return null;
		}

		$type = is_dir($path) ? File::TYPE_DIRECTORY : File::TYPE_FILE;

		// Directories don't have a modified time here
		if ($type == File::TYPE_DIRECTORY && $file->type == File::TYPE_DIRECTORY) {
			return $file;
		}

		$modified = filemtime($path);

		if ($modified <= $file->modified->getTimestamp()) {
			return $file;
		}

		if ($type == File::TYPE_DIRECTORY) {
			$file->modified = null;
			$file->size = null;
			$file->mime = null;
			$file->image = null;
		}
		else {
			// Short trick to return a local timezone date time
			$file->modified = \DateTime::createFromFormat('!Y-m-d H:i:s', date('Y-m-d H:i:s', $modified));
			$file->size = filesize($path);

			$finfo = \finfo_open(\FILEINFO_MIME_TYPE);
			$file->mime = finfo_file($finfo, $path);

			if ($type != $file->type) {
				$file->type = $type;
			}
		}

		$file->save();

		return $file;
	}
}
