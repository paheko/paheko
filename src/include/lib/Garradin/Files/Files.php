<?php

namespace Garradin\Files;

use Garradin\Static_Cache;
use Garradin\DB;
use Garradin\Plugin;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\ValidationException;
use Garradin\Users\Session;
use Garradin\Entities\Files\File;
use Garradin\Entities\Web\Page;

use KD2\DB\EntityManager as EM;
use KD2\ZipWriter;

use const Garradin\{FILE_STORAGE_BACKEND, FILE_STORAGE_QUOTA, FILE_STORAGE_CONFIG};

class Files
{
	/**
	 * To enable or disable quota check
	 */
	static protected $quota = true;

	static public function search(string $search, string $path = null): array
	{
		if (strlen($search) > 100) {
			throw new ValidationException('Recherche trop longue : maximum 100 caractères');
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
			snippet(files_search, \'<b>\', \'</b>\', \'…\', 2, -30) AS snippet,
			rank(matchinfo(files_search), 0, 1.0, 1.0) AS points
			FROM files_search
			WHERE files_search MATCH ? %s
			ORDER BY points DESC
			LIMIT 0,50;', $where);

		return DB::getInstance()->get($query, ...$params);
	}

	/**
	 * Returns a list of files and directories inside a parent path
	 * This is not recursive and will only return files and directories
	 * directly in the specified $parent path.
	 */
	static public function list(string $parent = ''): array
	{
		if ($parent !== '') {
			File::validatePath($parent);
		}

		// Update this path
		return self::callStorage('list', $parent);
	}

	/**
	 * Returns a list of files or directories matching a glob pattern
	 * only * and ? characters are supported in pattern
	 */
	static public function glob(string $pattern): array
	{
		return self::callStorage('glob', $pattern);
	}

	/**
	 * Creates a ZIP file archive from a $parent path
	 * @param  string  $parent  Parent path
	 * @param  Session $session Logged-in user session, if set access rights to the path will be checked,
	 * if left NULL, then no check will be made (!).
	 */
	static public function zip(string $parent, ?Session $session): void
	{
		$file = Files::get($parent);

		if (!$file) {
			throw new UserException('Ce répertoire n\'existe pas.');
		}

		if ($session && !$file->checkReadAccess($session)) {
			throw new UserException('Vous n\'avez pas accès à ce répertoire');
		}

		$zip = new ZipWriter('php://output');
		$zip->setCompression(0);

		$add_file = function ($subpath) use ($zip, $parent, &$add_file) {
			foreach (self::list($subpath) as $file) {
				if ($file->type == $file::TYPE_DIRECTORY) {
					$add_file($file->path);
					continue;
				}

				$dest_path = substr($file->path, strlen($parent . '/'));
				$zip->add($dest_path, null, $file->fullpath());
			}
		};

		$add_file($parent);

		$zip->close();
	}

	/**
	 * List files and directories inside a context (first-level directory)
	 */
	static public function listForContext(string $context, ?string $ref = null): array
	{
		$path = $context;

		if ($ref) {
			$path .= '/' . $ref;
		}

		return self::list($path);
	}

	/**
	 * Delete a specified file/directory path
	 */
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

			call_user_func([$from, 'lock']);
			call_user_func([$to, 'lock']);

			$db = DB::getInstance();
			$db->begin();
			$i = 0;

			self::migrateDirectory($from, $to, '', $i, $callback);
		}
		catch (UserException $e) {
			throw new \RuntimeException('Migration failed', 0, $e);
		}
		finally {
			$db->commit();
			call_user_func([$from, 'unlock']);
			call_user_func([$to, 'unlock']);
		}
	}

	static protected function migrateDirectory(string $from, string $to, string $path, int &$i, ?callable $callback)
	{
		$db = DB::getInstance();

		foreach (call_user_func([$from, 'list'], $path) as $file) {
			if (!$file->parent && $file->name == '.lock') {
				// Ignore lock file
				continue;
			}

			if (++$i >= 100) {
				$db->commit();
				$db->begin();
				$i = 0;
			}

			if ($file->type == File::TYPE_DIRECTORY) {
				call_user_func([$to, 'mkdir'], $file);
				self::migrateDirectory($from, $to, $file->path, $i, $callback);
			}
			else {
				$from_path = call_user_func([$from, 'getFullPath'], $file);
				call_user_func([$to, 'storePath'], $file, $from_path);
			}

			if (null !== $callback) {
				$callback($file);
			}

			unset($file);
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

		$file = self::callStorage('get', $path);

		if (!$file || ($type && $file->type != $type)) {
			return null;
		}

		return $file;
	}

	static public function exists(string $path): bool
	{
		return self::callStorage('exists', $path);
	}

	static public function getFromURI(string $uri): ?File
	{
		$uri = trim($uri, '/');
		$uri = rawurldecode($uri);

		return self::get($uri, File::TYPE_FILE);
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
		$path = '';

		foreach ($parts as $part) {
			$path = trim($path . '/' . $part, '/');
			$breadcrumbs[$path] = $part;
		}

		return $breadcrumbs;
	}

	static public function getQuota(): float
	{
		return FILE_STORAGE_QUOTA ?? self::callStorage('getQuota');
	}

	static public function getUsedQuota(bool $force_refresh = false): float
	{
		if ($force_refresh || Static_Cache::expired('used_quota', 3600)) {
			$quota = self::callStorage('getTotalSize');
			Static_Cache::store('used_quota', $quota);
		}
		else {
			$quota = (float) Static_Cache::get('used_quota');
		}

		return $quota;
	}

	static public function getRemainingQuota(bool $force_refresh = false): float
	{
		if (FILE_STORAGE_QUOTA !== null) {
			$quota = FILE_STORAGE_QUOTA - self::getUsedQuota($force_refresh);
		}
		else {
			$quota = self::callStorage('getRemainingQuota');
		}

		return max(0, $quota);
	}

	static public function checkQuota(int $size = 0): void
	{
		if (!self::$quota) {
			return;
		}

		$remaining = self::getRemainingQuota(true);

		if (($remaining - (float) $size) < 0) {
			throw new ValidationException('L\'espace disque est insuffisant pour réaliser cette opération');
		}
	}

	static public function enableQuota(): void
	{
		self::$quota = true;
	}

	static public function disableQuota(): void
	{
		self::$quota = false;
	}

	static public function getVirtualTableName(): string
	{
		if (FILE_STORAGE_BACKEND == 'SQLite') {
			return 'files';
		}

		return 'tmp_files';
	}

	static public function syncVirtualTable(string $parent = '', bool $recursive = false)
	{
		if (FILE_STORAGE_BACKEND == 'SQLite') {
			// No need to create a virtual table, use the real one
			return;
		}

		$db = DB::getInstance();
		$db->begin();

		$db->exec('CREATE TEMP TABLE IF NOT EXISTS tmp_files AS SELECT * FROM files WHERE 0;');

		foreach (Files::list($parent) as $file) {
			// Ignore additional directories
			if ($parent == '' && !array_key_exists($file->name, File::CONTEXTS_NAMES)) {
				continue;
			}

			$db->insert('tmp_files', $file->asArray(true));

			if ($recursive && $file->type === $file::TYPE_DIRECTORY) {
				self::syncVirtualTable($file->path, $recursive);
			}
		}

		$db->commit();
	}

	static protected function create(string $parent, string $name, ?string $source_path, ?string $source_content): File
	{
		if (!isset($source_path) && !isset($source_content)) {
			throw new \InvalidArgumentException('Either source path or source content should be set but not both');
		}

		File::validateFileName($name);
		File::validatePath($parent);
		self::ensureDirectoryExists($parent);

		$name = File::filterName($file['name']);

		$finfo = \finfo_open(\FILEINFO_MIME_TYPE);

		$path = $parent . '/' . $name;
		$file = Files::callStorage('get', $path) ?: new File;
		$file->import(compact('path', 'parent', 'name'));

		if ($source_path && !$source_content) {
			$file->set('mime', finfo_file($finfo, $source_path));
			$file->set('size', filesize($source_path));
			$file->set('modified', new \DateTime('@' . filemtime($source_path)));
		}
		else {
			$file->set('mime', finfo_buffer($finfo, $source_content));
			$file->set('size', strlen($source_content));
		}

		$file->set('image', in_array($file->mime, self::IMAGE_TYPES));

		// Force empty files as text/plain
		if ($file->mime == 'application/x-empty' && !$file->size) {
			$file->set('mime', 'text/plain');
		}

		return $file;
	}


	/**
	 * Create and store a file from a local path
	 * @param  string $path         Target parent path + name
	 * @param  string $source_path    Source file path
	 * @return File
	 */
	static public function createFromPath(string $path, string $source_path): File
	{
		$parent = Utils::dirname($path);
		$name = Utils::basename($path);
		$file = self::create($parent, $name, $source_path, null);
		$file->store($source_path, null);
		return $file;
	}

	/**
	 * Create and store a file from a string
	 * @param  string $path         Target parent path + name
	 * @param  string $source_content    Source file contents
	 * @return File
	 */
	static public function createFromString(string $path, string $source_content): File
	{
		$parent = Utils::dirname($path);
		$name = Utils::basename($path);
		$file = self::create($parent, $name, null, $source_content);
		$file->store(null, $source_content);
		return $file;
	}


	/**
	 * Upload multiple files
	 * @param  string $parent Target parent directory (eg. 'documents/Logos')
	 * @param  string $key  The name of the file input in the HTML form (this MUST have a '[]' at the end of the name)
	 * @return array list of File objects created
	 */
	static public function uploadMultiple(string $parent, string $key): array
	{
		if (!isset($_FILES[$key]['name'][0])) {
			throw new UserException('Aucun fichier reçu');
		}

		// Transpose array
		// see https://www.php.net/manual/en/features.file-upload.multiple.php#53240
		$files = Utils::array_transpose($_FILES[$key]);
		$out = [];

		// First check all files
		foreach ($files as $file) {
			if (!empty($file['error'])) {
				throw new UserException(self::getUploadErrorMessage($file['error']));
			}

			if (empty($file['size']) || empty($file['name'])) {
				throw new UserException('Fichier reçu invalide : vide ou sans nom de fichier.');
			}

			if (!is_uploaded_file($file['tmp_name'])) {
				throw new \RuntimeException('Le fichier n\'a pas été envoyé de manière conventionnelle.');
			}
		}

		// Then create files
		foreach ($files as $file) {
			$out[] = self::createFromPath($parent, $name, $file['tmp_name']);
		}

		return $out;
	}

	/**
	 * Upload a file using POST from a HTML form
	 * @param  string $parent Target parent directory (eg. 'documents/Logos')
	 * @param  string $key  The name of the file input in the HTML form
	 * @return self Created file object
	 */
	static public function upload(string $parent, string $key, ?string $name = null): self
	{
		if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
			throw new UserException('Aucun fichier reçu');
		}

		$file = $_FILES[$key];

		if (!empty($file['error'])) {
			throw new UserException(self::getUploadErrorMessage($file['error']));
		}

		if (empty($file['size']) || empty($file['name'])) {
			throw new UserException('Fichier reçu invalide : vide ou sans nom de fichier.');
		}

		if (!is_uploaded_file($file['tmp_name'])) {
			throw new \RuntimeException('Le fichier n\'a pas été envoyé de manière conventionnelle.');
		}

		$name = File::filterName($name ?? $file['name']);

		return self::createFromPath($parent, $name, $file['tmp_name']);
	}


	/**
	 * Récupération du message d'erreur
	 * @param  integer $error Code erreur du $_FILE
	 * @return string Message d'erreur
	 */
	static public function getUploadErrorMessage($error)
	{
		switch ($error)
		{
			case UPLOAD_ERR_INI_SIZE:
				return 'Le fichier excède la taille permise par la configuration.';
			case UPLOAD_ERR_FORM_SIZE:
				return 'Le fichier excède la taille permise par le formulaire.';
			case UPLOAD_ERR_PARTIAL:
				return 'L\'envoi du fichier a été interrompu.';
			case UPLOAD_ERR_NO_FILE:
				return 'Aucun fichier n\'a été reçu.';
			case UPLOAD_ERR_NO_TMP_DIR:
				return 'Pas de répertoire temporaire pour stocker le fichier.';
			case UPLOAD_ERR_CANT_WRITE:
				return 'Impossible d\'écrire le fichier sur le disque du serveur.';
			case UPLOAD_ERR_EXTENSION:
				return 'Une extension du serveur a interrompu l\'envoi du fichier.';
			default:
				return 'Erreur inconnue: ' . $error;
		}
	}

	/**
	 * Create a new directory
	 * @param  string $parent        Target parent path
	 * @param  string $name          Target name
	 * @param  bool   $create_parent Create parent directories if they don't exist
	 * @return self
	 */
	static public function mkdir(string $path, bool $create_parent = true): File
	{
		$path = trim($path, '/');
		$parent = Utils::dirname($path);
		$name = Utils::basename($path);

		$name = File::filterName($name);
		$path = $parent . '/' . $name;

		File::validatePath($path);
		Files::checkQuota();

		if (self::exists($path)) {
			throw new ValidationException('Le nom de répertoire choisi existe déjà: ' . $path);
		}

		if ($parent !== '' && $create_parent) {
			self::ensureDirectoryExists($parent);
		}

		$file = new File;
		$type = $file::TYPE_DIRECTORY;
		$file->import(compact('path', 'name', 'parent') + [
			'type'     => file::TYPE_DIRECTORY,
			'image'    => false,
		]);

		$file->modified = new \DateTime;

		Files::callStorage('mkdir', $file);

		Plugin::fireSignal('files.mkdir', compact('file'));

		return $file;
	}

	static public function ensureDirectoryExists(string $path): void
	{
		$db = DB::getInstance();
		$parts = explode('/', $path);
		$tree = '';

		foreach ($parts as $part) {
			$tree = trim($tree . '/' . $part, '/');
			$exists = $db->test(File::TABLE, 'type = ? AND path = ?', File::TYPE_DIRECTORY, $tree);

			if (!$exists) {
				try {
					self::mkdir($tree, false);
				}
				catch (ValidationException $e) {
					// Ignore when directory already exists
				}
			}
		}
	}
}
