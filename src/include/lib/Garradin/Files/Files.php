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
	static public function redirectOldWikiPage(string $uri): void {
		$uri = Utils::transformTitleToURI($uri);

		$db = DB::getInstance();

		if ($db->test(Page::TABLE, 'uri = ?')) {
			Utils::redirect('!web/page.php?uri=' . $uri);
		}
	}

	static public function search(string $search, string $path = null): array
	{
		if (strlen($search) > 100) {
			throw new UserException('Recherche trop longue : maximum 100 caractères');
		}

		$where = '';
		$params = [trim($search)];

		if (null !== $path) {
			$where = ' AND path LIKE ?';
			$params[] = $path;
		}

		$query = sprintf('SELECT
			*,
			dirname(path) AS parent,
			snippet(files_search, \'<b>\', \'</b>\', \'…\', 2) AS snippet,
			rank(matchinfo(files_search), 0, 1.0, 1.0) AS points
			FROM files_search
			WHERE files_search MATCH ? %s
			ORDER BY points DESC
			LIMIT 0,50;', $where);

		return DB::getInstance()->get($query, ...$params);
	}

	static public function list(string $parent = ''): array
	{
		if ($parent !== '') {
			File::validatePath($parent);
		}

		// Update this path
		self::callStorage('sync', $parent);

		return EM::getInstance(File::class)->all('SELECT * FROM @TABLE WHERE parent = ? ORDER BY type DESC, name COLLATE NOCASE ASC;', $parent);
	}

	static public function listAllDirectoriesAssoc(string $context): array
	{
		return DB::getInstance()->getAssoc('SELECT
			path,
			REPLACE(path || \'/\', ?, \'\')
			FROM files WHERE (parent = ? OR parent LIKE ?) AND type = ? ORDER BY path COLLATE NOCASE, name COLLATE NOCASE;', $context, $context, $context . '/%', File::TYPE_DIRECTORY);
	}

	static public function delete(string $path): void
	{
		$file = self::get($path);

		if (!$file) {
			return;
		}

		$file->delete();
	}

	static public function callStorage(string $function, ...$args)
	{
		$class_name = __NAMESPACE__ . '\\Storage\\' . FILE_STORAGE_BACKEND;

		call_user_func([$class_name, 'configure'], FILE_STORAGE_CONFIG);

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
			$i = 0;

			self::migrateDirectory($from, $to, '', $i, $callback);

			$db->commit();
		}
		finally {
			call_user_func([$from, 'unlock']);
			call_user_func([$to, 'unlock']);
		}
	}

	static protected function migrateDirectory(string $from, string $to, string $path, int &$i, ?callable $callback)
	{
		$db = DB::getInstance();
		call_user_func([$from, 'sync'], $path);

		foreach ($db->iterate('SELECT * FROM files WHERE parent = ?;', $path) as $file) {
			if (++$i >= 50) {
				$db->commit();
				$db->begin();
				$i = 0;
			}

			$f = new File;
			$f->load((array) $file);
			$f->exists(true);

			if ($f->type == File::TYPE_DIRECTORY) {
				call_user_func([$to, 'mkdir'], $f);
				self::migrateDirectory($from, $to, trim($path . '/' . $f->name, '/'), $i, $callback);
			}
			else {
				$from_path = call_user_func([$from, 'getFullPath'], $f);
				call_user_func([$to, 'storePath'], $f, $from_path);
			}

			if (null !== $callback) {
				$callback($f);
			}

			unset($f);
		}
	}

	/**
	 * Delete all files from a storage backend
	 */
	static public function truncateStorage(string $backend, $config = null): void
	{
		$backend = __NAMESPACE__ . '\\Storage\\' . $backend;

		call_user_func([$backend, 'configure'], $config);

		if (!class_exists($backend)) {
			throw new \InvalidArgumentException('Invalid storage: ' . $backend);
		}

		call_user_func([$backend, 'truncate']);
	}

	static public function get(string $path, int $type = null): ?File
	{
		try {
			File::validatePath($path);
		}
		catch (ValidationException $e) {
			return null;
		}

		$where = '';

		if (null !== $type) {
			$where = ' AND type = ' . $type;
		}

		$sql = sprintf('SELECT * FROM @TABLE WHERE path = ? %s LIMIT 1;', $where);

		$file = EM::findOne(File::class, $sql, $path);

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
		if (!array_key_exists($context, File::CONTEXTS_NAMES)) {
			$uri = File::CONTEXT_WEB . '/' . $uri;
		}

		return self::get($uri, null, File::TYPE_FILE);
	}

	static public function getContext(string $path): ?string
	{
		$context = strtok($path, '/');

		if (!array_key_exists($context, File::CONTEXTS_NAMES)) {
			return null;
		}

		return $context;
	}

	static public function getContextRef(string $path): ?string
	{
		$context = strtok($path, '/');
		return strtok('/') ?: null;
	}

	static public function getBreadcrumbs(string $path): array
	{
		$parts = explode('/', $path);
		$breadcrumbs = [];

		foreach ($parts as $part) {
			$path = trim(key($breadcrumbs) . '/' . $part, '/');
			$breadcrumbs[$path] = $part;
		}

		return $breadcrumbs;
	}

	static public function getQuota(): int
	{
		return FILE_STORAGE_QUOTA ?: self::callStorage('getQuota');
	}

	static public function getUsedQuota(): int
	{
		return self::callStorage('getTotalSize');
	}

	static public function checkQuota(int $size = 0): void
	{
		$quota = self::getQuota();
		$used = self::callStorage('getTotalSize');

		if (($used + $size) >= $quota) {
			throw new ValidationException('L\'espace disque est insuffisant pour réaliser cette opération');
		}
	}
}
