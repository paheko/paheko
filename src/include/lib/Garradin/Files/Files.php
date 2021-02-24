<?php

namespace Garradin\Files;

use Garradin\Static_Cache;
use Garradin\DB;
use Garradin\Utils;
use Garradin\ValidationException;
use Garradin\Membres\Session;
use Garradin\Entities\Files\File;
use Garradin\Entities\Web\Page;

use KD2\DB\EntityManager as EM;

use const Garradin\{FILE_STORAGE_BACKEND, FILE_STORAGE_QUOTA, FILE_STORAGE_CONFIG};

class Files
{
	static public function redirectOldWikiPage(string $uri): ?File {
		$uri = Utils::transformTitleToURI($uri);

		$db = DB::getInstance();

		if ($db->test(Page::TABLE, 'uri = ?')) {
			Utils::redirect(ADMIN_URL . 'web/page.php?uri=' . $uri);
		}

		$file = self::get('documents/wiki/' . $uri . '.skriv');

		if ($file) {
			Utils::redirect(ADMIN_URL . 'documents/?p=' . $file->path);
		}

		return null;
	}

	static public function list(string $path = null): array
	{
		File::validatePath($path);

		// Update this path
		self::callStorage('sync', $path);

		return EM::getInstance(File::class)->all('SELECT * FROM @TABLE WHERE path = ? ORDER BY type = ?, name ASC;', $path, File::TYPE_DIRECTORY);
	}

	/**
	 * Creates a new temporary table files_tmp containg all files from the path argument
	 */
	static public function listToSQL(string $path): int
	{
		$db = DB::getInstance();
		$db->begin();

		$columns = File::getColumns();
		$db->exec(sprintf('CREATE TEMP TABLE IF NOT EXISTS files_tmp (%s);', implode(',', $columns)));

		$i = 0;

		foreach (self::list($path) as $file) {
			$file = $file->asArray();
			unset($file['id']);
			$db->insert('files_tmp', $file);
			$i++;
		}

		$db->commit();
		return $i;
	}

	static public function callStorage(string $function, ...$args)
	{
		$storage = FILE_STORAGE_BACKEND ?? 'SQLite';
		$class_name = __NAMESPACE__ . '\\Storage\\' . $storage;

		call_user_func([$class_name, 'configure'], FILE_STORAGE_CONFIG);

		// Check that we can store this data
		if ($function == 'store') {
			$quota = FILE_STORAGE_QUOTA ?: self::callStorage('getQuota');
			$used = self::callStorage('getTotalSize');

			$size = $args[0] ? filesize($args[1]) : strlen($args[2]);

			if (($used + $size) >= $quota) {
				throw new \OutOfBoundsException('File quota has been exhausted');
			}
		}

		return call_user_func_array([$class_name, $function], $args);
	}

	/**
	 * Copy all files from a storage backend to another one
	 * This can be used to move from SQLite to FileSystem for example
	 * Note that this only copies files, and is not removing them from the source storage backend.
	 */
	static public function migrateStorage(string $from, string $to, $from_config = null, $to_config = null, ?callable $callback = null): void
	{
		$from = __NAMESPACE__ . '\\Storage\\' . $from;
		$to = __NAMESPACE__ . '\\Storage\\' . $to;

		if (!class_exists($from)) {
			throw new \InvalidArgumentException('Invalid storage: ' . $from);
		}

		if (!class_exists($to)) {
			throw new \InvalidArgumentException('Invalid storage: ' . $to);
		}

		call_user_func([$from, 'configure'], $from_config);
		call_user_func([$to, 'configure'], $to_config);

		try {
			call_user_func([$from, 'checkLock']);
			call_user_func([$to, 'checkLock']);

			//call_user_func([$from, 'lock']);
			//call_user_func([$to, 'lock']);

			$db = DB::getInstance();
			$db->begin();

			self::migrateDirectory($from, $to, '', $callback);

			$db->commit();
		}
		finally {
			call_user_func([$from, 'unlock']);
			call_user_func([$to, 'unlock']);
		}
	}

	static protected function migrateDirectory(string $from, string $to, string $path, callable $callback)
	{
		foreach (call_user_func([$from, 'list'], $path) as $file) {
			if ($file['type'] == File::TYPE_DIRECTORY) {
				self::migrateDirectory($from, $to, ($path ? $path . '/' : '') . $file['name'], $callback);
				continue;
			}

			$f = new File;
			$f->load($file);

			$from_path = call_user_func([$from, 'getFullPath'], $f->path());
			call_user_func([$to, 'store'], $f, $from_path, null);

			if (null !== $callback) {
				$callback($f);
			}
		}
	}

	/**
	 * Delete all files from a storage backend
	 */
	static public function resetStorage(string $backend, $config = null): void
	{
		$backend = __NAMESPACE__ . '\\Storage\\' . $backend;

		call_user_func([$backend, 'configure'], $config);

		if (!class_exists($backend)) {
			throw new \InvalidArgumentException('Invalid storage: ' . $from);
		}

		call_user_func([$backend, 'reset']);
	}

	static public function getContextJoinClause(string $context): ?string
	{
		switch ($context) {
			case File::CONTEXT_TRANSACTION:
				return 'acc_transactions c ON c.id = f.context_ref';
			case File::CONTEXT_USER:
				return 'membres c ON c.id = f.context_ref';
			case File::CONTEXT_FILE:
				return 'files c ON c.id = f.context_ref';
			case File::CONTEXT_CONFIG:
				return 'config c ON c.key = f.context_ref AND c.value = f.id';
			case File::CONTEXT_WEB:
			case File::CONTEXT_DOCUMENTS:
			case File::CONTEXT_SKELETON:
			default:
				return null;
		}
	}

	static public function get(?string $path, ?string $name = null): ?File
	{
		if (null === $path) {
			return null;
		}

		$fullpath = $path;

		if ($name) {
			$fullpath .= '/' . $name;
		}

		try {
			File::validatePath($fullpath);
		}
		catch (ValidationException $e) {
			return null;
		}

		$path = dirname($fullpath);
		$name = basename($fullpath);

		$file = EM::findOne(File::class, 'SELECT * FROM @TABLE WHERE path = ? AND name = ? LIMIT 1;', $path, $name);

		if (null !== $file) {
			$file = self::callStorage('update', $file);
		}

		return $file;
	}

	static public function getFromURI(string $uri): ?File
	{
		$uri = trim($uri, '/');
		$uri = rawurldecode($uri);

		$context = substr($uri, 0, strpos($uri, '/'));

		// Use alias for web files
		if (!in_array($context, File::CONTEXTS_NAMES)) {
			$uri = File::CONTEXT_WEB . '/' . $uri;
		}

		return self::get($uri);
	}
}
