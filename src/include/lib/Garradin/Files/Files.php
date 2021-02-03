<?php

namespace Garradin\Files;

use Garradin\Static_Cache;
use Garradin\DB;
use Garradin\Utils;
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

		$id = $db->firstColumn('SELECT id FROM files WHERE name = ? AND context != ?;', $uri . '.skriv', File::CONTEXT_WEB);

		if ($id) {
			Utils::redirect(ADMIN_URL . 'files/file.php?id=' . $id);
		}

		return null;
	}

	static public function list(string $path = null): array
	{
		File::validatePath($path);

		$list = self::callStorage('list', $path);

		foreach ($list as &$item) {
			$file = new File;
			$file->import((array) $item);
			$item = $file;
		}

		return $list;
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

			call_user_func([$from, 'lock']);
			call_user_func([$to, 'lock']);

			$db = DB::getInstance();
			$db->begin();
			$res = EM::getInstance(File::class)->iterate('SELECT * FROM @TABLE;');

			foreach ($res as $file) {
				$from_path = call_user_func([$from, 'getPath'], $file);
				call_user_func([$to, 'store'], $file, $from_path, null);

				if (null !== $callback) {
					$callback($file);
				}
			}

			$db->commit();
		}
		finally {
			call_user_func([$from, 'unlock']);
			call_user_func([$to, 'unlock']);
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

	static public function deletePath(string $context, $value): void
	{
		if (null === $value) {
			throw new \InvalidArgumentException('value argument cannot be null');
		}

		foreach (self::iterateLinkedTo($context, $value) as $file) {
			$file->delete();
		}
	}

	static public function iterateLinkedTo(string $context, $value = null): \Generator
	{
		if (!array_key_exists($context, File::CONTEXTS_NAMES)) {
			throw new \InvalidArgumentException('Invalid context');
		}

		$db = DB::getInstance();
		$where = $value !== null ? sprintf(' AND f.context_ref = %s', $db->quote($value)) : '';
		$sql = sprintf('SELECT f.* FROM @TABLE f INNER JOIN %s WHERE 1 %s;', self::getContextJoinClause($context), $where);

		return EM::getInstance(File::class)->iterate($sql);
	}

	static public function listLinkedFiles(string $context, $value = null): array
	{
		return iterator_to_array(self::iterateLinkedTo($context, $value));
	}

	static public function get(string $path, ?string $name = null): ?File
	{
		if ($name) {
			$path .= '/' . $name;
		}

		$info = self::callStorage('stat', $path);

		if (!$info) {
			return null;
		}

		$file = new File;
		$file->path = dirname($path);
		$file->name = basename($path);
		$file->import($info);

		return $file;
	}
}
