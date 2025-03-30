<?php

namespace Paheko\Entities\Files;

use KD2\HTTP\Server;
use KD2\Graphics\Image;
use KD2\Graphics\Blob;
use KD2\Office\ToText;
use KD2\DB\EntityManager;

use Paheko\Config;
use Paheko\DB;
use Paheko\Entity;
use Paheko\Files\Conversion;
use Paheko\Form;
use Paheko\Plugins;
use Paheko\Static_Cache;
use Paheko\Template;
use Paheko\UserException;
use Paheko\ValidationException;
use Paheko\Users\Session;
use Paheko\Utils;
use Paheko\Entities\Web\Page;
use Paheko\Web\Render\Render;
use Paheko\Web\Router;
use Paheko\Web\Cache as Web_Cache;
use Paheko\Users\DynamicFields;
use Paheko\UserTemplate\CommonFunctions;

use Paheko\Files\Files;

use const Paheko\{WWW_URL, BASE_URL, ENABLE_XSENDFILE, WOPI_DISCOVERY_URL, HOSTING_PROVIDER};

class File extends Entity
{
	use FilePermissionsTrait;
	use FileThumbnailTrait;
	use FileVersionsTrait;
	use FileSharingTrait;
	use FileWOPITrait;

	const TABLE = 'files';

	protected ?int $id;

	/**
	 * Unique file identifier as a random string
	 */
	protected string $hash_id;

	/**
	 * Parent directory of file
	 */
	protected ?string $parent = null;

	/**
	 * File name
	 */
	protected string $name;

	/**
	 * Complete file path (parent + '/' + name)
	 */
	protected string $path;

	/**
	 * Type of file: file or directory
	 */
	protected int $type = self::TYPE_FILE;
	protected ?string $mime = null;
	protected ?int $size = null;
	protected \DateTime $modified;
	protected bool $image;
	protected ?string $md5;
	protected ?\DateTime $trash = null;

	const TYPE_FILE = 1;
	const TYPE_DIRECTORY = 2;

	/**
	 * Tailles de miniatures autorisées, pour ne pas avoir 500 fichiers générés avec 500 tailles différentes
	 * @var array
	 */
	const ALLOWED_THUMB_SIZES = [
		'150px' => [['trim'], ['resize', 150]],
		'250px' => [['trim'], ['resize', 250]],
		'500px' => [['resize', 500]],
		'750px' => [['resize', 750]],
		'crop-256px' => [['trim'], ['cropResize', 256, 256]],
	];

	const THUMB_CACHE_ID = 'file.thumb.%s.%s';

	const THUMB_SIZE_TINY = '250px';
	const THUMB_SIZE_SMALL = '500px';
	const THUMB_SIZE_LARGE = '750px';

	const CONTEXT_TRASH = 'trash';
	const CONTEXT_DOCUMENTS = 'documents';
	const CONTEXT_USER = 'user';
	const CONTEXT_TRANSACTION = 'transaction';
	const CONTEXT_CONFIG = 'config';
	const CONTEXT_WEB = 'web';
	const CONTEXT_MODULES = 'modules';
	const CONTEXT_ATTACHMENTS = 'attachments';
	const CONTEXT_VERSIONS = 'versions';
	const CONTEXT_EXTENSIONS = 'ext';

	const CONTEXTS_NAMES = [
		self::CONTEXT_TRASH => 'Corbeille',
		self::CONTEXT_DOCUMENTS => 'Documents',
		self::CONTEXT_USER => 'Fiches des membres',
		self::CONTEXT_TRANSACTION => 'Écritures comptables',
		self::CONTEXT_CONFIG => 'Configuration',
		self::CONTEXT_WEB => 'Site web',
		self::CONTEXT_MODULES => 'Code des modules',
		self::CONTEXT_ATTACHMENTS => 'Fichiers joints aux messages',
		self::CONTEXT_VERSIONS => 'Versions',
		self::CONTEXT_EXTENSIONS => 'Extensions',
	];

	const VERSIONED_CONTEXTS = [
		self::CONTEXT_DOCUMENTS,
		self::CONTEXT_TRANSACTION,
		self::CONTEXT_USER,
	];

	const IMAGE_TYPES = [
		'image/png',
		'image/gif',
		'image/jpeg',
		'image/webp',
	];

	const PREVIEW_TYPES = [
		// We expect modern browsers to be able to preview a PDF file
		// even if the user has disabled PDF opening in browser
		// (something we cannot detect)
		'application/pdf',
		'audio/mpeg',
		'audio/ogg',
		'audio/wave',
		'audio/wav',
		'audio/x-wav',
		'audio/x-pn-wav',
		'audio/webm',
		'video/webm',
		'video/ogg',
		'video/mp4',
		'application/ogg',
		'image/png',
		'image/gif',
		'image/jpeg',
		'image/webp',
		'image/svg+xml',
		'text/plain',
		'text/html',
	];

	const FORBIDDEN_CHARACTERS = [
		'..', // double dot
		"\0", // NUL
		'/', // slash
		'\\', // anti-slash
		// invalid characters in Windows
		'\\', ':', '*', '?', '"', '<', '>', '|',
	];

	// https://book.hacktricks.xyz/pentesting-web/file-upload
	const FORBIDDEN_EXTENSIONS = '!^(?:cgi|exe|sh|bash|com|pif|jspx?|jar|js[wxv]|action|do|php(?:s|\d+)?|pht|phtml?|shtml|phar|htaccess|inc|cfml?|cfc|dbm|swf|pl|perl|py|pyc|asp|so)$!i';

	public function selfCheck(): void
	{
		$this->assert($this->type === self::TYPE_DIRECTORY || $this->type === self::TYPE_FILE, 'Unknown file type');
		$this->assert($this->type === self::TYPE_DIRECTORY || $this->size !== null, 'File size must be set');
		$this->assert(trim($this->name) !== '', 'Le nom de fichier ne peut rester vide');
		$this->assert(strlen($this->path), 'Le chemin ne peut rester vide');
		$this->assert(null === $this->parent || strlen($this->parent), 'Le chemin ne peut rester vide');
		$this->assert(false === strpos($this->path, '//'));
		$this->assert(null === $this->parent || false === strpos($this->parent, '//'));
	}

	public function save(bool $selfcheck = true): bool
	{
		if ($this->parent) {
			Files::ensureDirectoryExists($this->parent);
		}

		if (!isset($this->hash_id)) {
			$this->set('hash_id', Utils::random_string(12));
		}

		$ok = parent::save();

		$context = $this->context();

		// Link file to transaction/user
		if ($ok && $this->type === self::TYPE_FILE && in_array($context, [self::CONTEXT_USER, self::CONTEXT_TRANSACTION])) {
			// Only insert if ID exists in table
			$db = DB::getInstance();

			if ($context == self::CONTEXT_USER) {
				$id = (int)Utils::basename(Utils::dirname($this->parent));
				$field = Utils::basename($this->parent);

				if (!$id || !$field) {
					return $ok;
				}

				// The field does not exist anymore, don't link
				if (!DynamicFields::get($field)) {
					return $ok;
				}

				$sql = sprintf('INSERT OR IGNORE INTO %s_files (id_file, id_user, field) SELECT %d, %d, %s FROM %1$s WHERE id = %3$d;',
					'users',
					$this->id(),
					$id,
					$db->quote($field)
				);
			}
			else {
				$id = (int)Utils::basename($this->parent);

				if (!$id) {
					return $ok;
				}

				$sql = sprintf('INSERT OR IGNORE INTO %s_files (id_file, id_transaction) SELECT %d, %d FROM %1$s WHERE id = %3$d;',
					'acc_transactions',
					$this->id(),
					$id
				);
			}

			$db->exec($sql);
		}

		return $ok;
	}

	public function context(): string
	{
		$value = strtok($this->path, '/');
		strtok('');
		return $value;
	}

	public function parent(): File
	{
		return Files::get($this->parent);
	}

	public function getLocalFilePath(): ?string
	{
		$path = Files::callStorage('getLocalFilePath', $this);

		if (null === $path || !file_exists($path)) {
			return null;
		}

		return $path;
	}

	/**
	 * Will return NULL if file contents are not found in storage
	 */
	public function getLocalOrCacheFilePath(): ?string
	{
		$path = $this->getLocalFilePath();

		// If the file is not stored locally, copy the file to a local cache, using pointer
		if (null === $path) {
			$id = 'file-cache-' . $this->hash_id;
			$path = Static_Cache::getPath($id);

			if (!Static_Cache::hasExpired($id)) {
				return $path;
			}

			$pointer = $this->getReadOnlyPointer();

			if (!$pointer) {
				return null;
			}

			Static_Cache::storeFromPointer($id, $this->getReadOnlyPointer());
		}

		return $path;
	}

	public function etag(): string
	{
		if (isset($this->md5)) {
			return $this->md5;
		}
		elseif (!$this->isDir()) {
			return md5($this->path . $this->size . $this->modified->getTimestamp());
		}
		else {
			return md5($this->path . $this->getRecursiveSize() . $this->getRecursiveLastModified());
		}
	}

	public function getShortEtag(): string
	{
		return substr($this->etag(), 0, 10);
	}

	public function rehash($pointer = null): void
	{
		if ($this->isDir()) {
			return;
		}

		$path = !$pointer ? $this->getLocalFilePath() : null;

		if ($path) {
			$hash = md5_file($path);
		}
		else {
			$p = $pointer ?? $this->getReadOnlyPointer();

			if (!$p) {
				return;
			}

			$hash = hash_init('md5');

			while (!feof($p)) {
				hash_update($hash, fread($p, 8192));
			}

			$hash = hash_final($hash);

			if (null === $pointer) {
				fclose($p);
			}
			else {
				fseek($pointer, 0, SEEK_SET);
			}
		}

		$this->set('md5', $hash);
	}

	/**
	 * Return TRUE if the file can be previewed natively in a browser
	 * @return bool
	 */
	public function canPreview(): bool
	{
		if (in_array($this->mime, self::PREVIEW_TYPES)) {
			return true;
		}

		if (!WOPI_DISCOVERY_URL) {
			return false;
		}

		if ($this->getWopiURL()) {
			return true;
		}

		return false;
	}

	public function moveToTrash(): void
	{
		if ($this->context() === self::CONTEXT_TRASH) {
			return;
		}

		$db = DB::getInstance();
		$db->begin();

		// We need to put files in a specific subdirectory
		// or we might overwrite files previously put in trash
		// (for example you move "accounting/report.ods" to "trash/accounting/reports.ods",
		// then you move "accounting/" to "trash/accounting/", this would delete the previously
		// trashed "reports.ods" file)
		$hash = md5($this->path);
		$hash = substr(date('Y-m-d.His.') . $hash, 0, 40);

		// Only mark the root folder as trashed, but still move everything else
		$this->set('trash', new \DateTime);

		// Move versions as well
		if ($v = Files::get(self::CONTEXT_VERSIONS . '/' . $this->path)) {
			$v->rename(self::CONTEXT_TRASH . '/' . $hash . '/' . $v->path);
		}

		// ->rename() will ->save()
		$this->rename(self::CONTEXT_TRASH . '/' . $hash . '/' . $this->path);

		Plugins::fire('file.trash', false, ['file' => $this]);

		$db->commit();
	}

	public function restoreFromTrash(): void
	{
		if ($this->context() !== self::CONTEXT_TRASH) {
			return;
		}

		$db = DB::getInstance();
		$db->begin();

		$root = strtok($this->path, '/') . '/' . strtok('/');
		$orig_path = strtok('');

		$this->set('trash', null);

		$v = Files::get($root . '/' . self::CONTEXT_VERSIONS . '/' . $orig_path);

		// Restore versions
		if ($v) {
			$v->rename(self::CONTEXT_VERSIONS . '/' . $orig_path);
		}

		// rename() will do the save()
		$this->rename($orig_path);

		Plugins::fire('file.restore', false, ['file' => $this]);

		$db->commit();
	}

	public function deleteLocalFileCache(): void
	{
		if (isset($this->hash_id)) {
			// Remove any local file cache
			$id = 'file-cache-' . $this->hash_id;
			Static_Cache::remove($id);
		}
	}

	public function deleteCache(): void
	{
		// This also deletes thumbnail links
		Web_Cache::delete($this->uri());
		$this->deleteThumbnails();
		$this->deleteLocalFileCache();
	}

	/**
	 * Delete file from local database, but not the file from the storage itself
	 */
	public function deleteSafe(): bool
	{
		$this->deleteCache();
		return parent::delete();
	}

	public function delete(): bool
	{
		Files::assertStorageIsUnlocked();

		$db = DB::getInstance();
		$db->begin();

		// Delete actual file content
		$ok = Files::callStorage('delete', $this);

		// Also delete sub-directories and files, if the storage backend is not able to do it
		// (eg. object storage)
		if (!$ok && $this->type == self::TYPE_DIRECTORY) {
			foreach (Files::list($this->path) as $file) {
				if (!$file->delete()) {
					$db->rollback();
					return false;
				}
			}
		}
		elseif (!$ok) {
			throw new \LogicException('Storage backend couldn\'t delete a file');
		}

		Plugins::fire('file.delete', false, ['file' => $this]);

		$this->deleteCache();
		$this->deleteVersions();

		$r = parent::delete();

		$db->commit();

		return $r;
	}


	/**
	 * Copy the current file to a new location
	 * @param  string $target Target path
	 * @return self
	 */
	public function copy(string $new_path, ?Session $session = null): self
	{
		if ($this->isDir()) {
			throw new \LogicException('Cannot copy a directory');
		}

		$path = $this->getLocalFilePath();
		$pointer = $path ? null : $this->getReadOnlyPointer();

		return Files::createFrom($new_path, compact('path', 'pointer'), $session);
	}

	/**
	 * Change ONLY the file name, not the parent path
	 */
	public function changeFileName(string $new_name, ?Session $session = null, bool $check_exists = false): bool
	{
		self::validateFileName($new_name);

		$v = $this->getVersionsDirectory();

		$r = $this->rename(ltrim($this->parent . '/' . $new_name, '/'), $session, $check_exists);

		// Rename versions directory as well
		if ($v && $r) {
			$v->changeFileName($new_name, $session);
		}

		return $r;
	}

	/**
	 * Change ONLY the directory where the file is
	 * @param  string $target New directory path
	 * @return bool
	 */
	public function move(string $target): bool
	{
		$v = $this->getVersionsDirectory();

		$r = $this->rename($target . '/' . $this->name);

		if ($r && $v) {
			$v->rename(self::CONTEXT_VERSIONS . '/' . $this->path);
		}

		return $r;
	}

	/**
	 * Rename a file, this can include moving it (the UNIX way)
	 * @param  string $new_path Target path
	 * @return bool
	 */
	public function rename(string $new_path, ?Session $session = null, bool $check_exists = false): bool
	{
		$name = Utils::basename($new_path);

		self::validatePath($new_path);
		self::validateFileName($name);

		if ($session !== null) {
			self::validateCanHTML($name, $new_path, $session);
		}

		if ($new_path == $this->path) {
			throw new UserException(sprintf('Impossible de renommer "%s" lui-même', $this->path));
		}

		if (0 === strpos($new_path . '/', $this->path . '/')) {
			throw new UserException(sprintf('Impossible de renommer "%s" vers "%s"', $this->path, $new_path));
		}

		$parent = Utils::dirname($new_path);
		$is_dir = $this->isDir();

		$db = DB::getInstance();
		$db->begin();

		// Does the target already exist?
		$exists = Files::get($new_path);

		if ($exists && $check_exists) {
			throw new UserException('Un fichier de ce nom existe déjà.');
		}

		// List sub-files and sub-directories now, before they are changed
		$list = $is_dir ? Files::list($this->path) : [];

		// Make sure parent target directory exists
		Files::ensureDirectoryExists($parent);

		// Save current object for storage use
		$old = clone $this;

		// Update internal values
		$this->set('parent', $parent);
		$this->set('path', $new_path);
		$this->set('name', $name);

		// If the target does not exist already, move the current file now
		// this will avoid ensureDirectoryExists to create a duplicate
		if (!$exists) {
			$r = $this->save();
		}

		// Move each file to the new target
		if ($is_dir) {
			foreach ($list as $file) {
				$file->set('trash', $this->trash);
				$file->move($new_path . trim(substr($file->parent, strlen($old->path)), '/'), $session);
			}
		}

		if ($exists) {
			if ($is_dir) {
				// Make sure trash state is transmitted to target path
				$exists->set('trash', $this->trash);
				$r = $exists->save();

				// We assume that at this point, everything inside the source directory
				// has been moved to the existing target directory
				// So we can delete the source directory from the database
				// (both $this and $exists point to the same path, so we can't save $this)
				parent::delete();
				$db->commit();

				return $r;
			}
			else {
				// Overwrite existing file
				$exists->deleteSafe();
			}

			unset($exists);
			$r = $this->save();
		}

		if (!$is_dir) {
			// Actually move the file
			Files::callStorage('rename', $old, $new_path);
		}

		Plugins::fire('file.rename', false, ['file' => $this, 'new_path' => $new_path]);

		$db->commit();

		return $r;
	}

	public function setContent(string $content): self
	{
		$this->store(['content' => rtrim($content)]);
		return $this;
	}

	/**
	 * Store contents in file, either from a local path, from a binary string or from a pointer
	 *
	 * @param  array $source [path, content or pointer]
	 * @param  string $source_content
	 * @param  bool   $index_search Set to FALSE if you don't want the document to be indexed in the file search
	 * @return self
	 */
	public function store(array $source): self
	{
		if (!$this->path || !$this->name) {
			throw new \LogicException('Cannot store a file that does not have a target path and name');
		}

		if ($this->type == self::TYPE_DIRECTORY) {
			throw new \LogicException('Cannot store a directory');
		}

		if (!isset($source['path']) && !isset($source['content']) && !isset($source['pointer'])) {
			throw new \InvalidArgumentException('Unknown source type');
		}
		elseif (count(array_filter($source, fn($a) => !is_null($a))) != 1) {
			throw new \InvalidArgumentException('Invalid source type');
		}

		Files::assertStorageIsUnlocked();

		$path = $source['path'] ?? null;
		$content = $source['content'] ?? null;
		$pointer = $source['pointer'] ?? null;
		$new = !$this->exists();

		if ($path) {
			$this->set('size', filesize($path));
			Files::checkQuota($this->size);
			$this->set('md5', md5_file($path));
		}
		elseif (null !== $content) {
			$this->set('size', strlen($content));
			Files::checkQuota($this->size);
			$this->set('md5', md5($content));
		}
		elseif ($pointer) {
			// See https://github.com/php/php-src/issues/9441
			$meta = stream_get_meta_data($pointer);

			if (isset($meta['uri']) && $meta['uri'] == 'php://input') {
				while (!feof($pointer)) {
					fread($pointer, 8192);
				}
			}
			elseif (0 !== fseek($pointer, 0, SEEK_END)) {
				throw new \RuntimeException('Stream is not seekable');
			}

			$this->set('size', ftell($pointer));
			fseek($pointer, 0, SEEK_SET);
			Files::checkQuota($this->size);

			$this->rehash($pointer);
		}

		// File hasn't changed
		if (!$new && !$this->isModified('md5')) {
			return $this;
		}

		// Check that it's a real image
		if ($this->image) {
			if ($path) {
				$blob = file_get_contents($path, false, null, 0, 1000);
			}
			elseif ($pointer) {
				$blob = fread($pointer, 1000);
				fseek($pointer, 0, SEEK_SET);
			}
			else {
				$blob = substr($content, 0, 1000);
			}

			if ($size = Blob::getSize($blob)) {
				// This is to avoid pixel flood attacks
				if ($size[0] > 8000 || $size[1] > 8000) {
					throw new ValidationException('Cette image est trop grande (taille max 8000 x 8000 pixels)');
				}

				// Recompress PNG files from base64, assuming they are coming
				// from JS canvas which doesn't know how to gzip (d'oh!)
				if ($size[2] == 'image/png' && null !== $content) {
					$i = Image::createFromBlob($content);
					$content = $i->output('png', true);
					$this->set('size', strlen($content));
					unset($i);
				}
			}
			elseif (Blob::getType($blob)) {
				// WebP is fine, but we cannot get its size
			}
			else {
				// Not an image
				$this->set('image', false);
			}
		}

		$db = DB::getInstance();
		$db->begin();

		// Set modified time if not already set before
		if (!$this->isModified('modified')) {
			$this->set('modified', new \DateTime);
		}

		// Only archive previous version if it was more than 0 bytes
		if (!$new && $this->getModifiedProperty('size') !== 0 && $this->size > 0) {
			$this->createVersion();
			$this->pruneVersions();
		}

		// Save metadata now, and rollback if required
		$this->save();

		try {
			if (null !== $path) {
				$return = Files::callStorage('storePath', $this, $path);
			}
			elseif (null !== $content) {
				$return = Files::callStorage('storeContent', $this, $content);
			}
			else {
				$return = Files::callStorage('storePointer', $this, $pointer);
			}

			if (!$return) {
				throw new UserException('Le fichier n\'a pas pu être enregistré.');
			}

			Plugins::fire('file.store', false, ['file' => $this]);

			if (!$new) {
				Plugins::fire('file.overwrite', false, ['file' => $this]);
			}
			else {
				Plugins::fire('file.create', false, ['file' => $this]);
			}

			$this->deleteCache();

			$db->commit();

			// Index regular files, not directories
			if ($this->type == self::TYPE_FILE) {
				$this->indexForSearch($content);
			}

			return $this;
		}
		catch (\Exception $e) {
			if ($db->inTransaction()) {
				$db->rollback();
			}

			throw $e;
		}
		finally {
			if (null !== $pointer) {
				fclose($pointer);
			}
		}
	}

	public function indexForSearch(?string $content = null, ?string $title = null, ?string $forced_mime = null): void
	{
		$mime = $forced_mime ?? $this->mime;
		$ext = $this->extension();

		if ($this->isDir() && (!$mime || !$content)) {
			return;
		}

		// Store content in search table
		if (substr($mime, 0, 5) == 'text/') {
			$content ??= $this->fetch();

			if ($mime === 'text/html' || $mime == 'text/xml') {
				$content = html_entity_decode(strip_tags($content),  ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
			}
		}
		elseif ($ext && Conversion::canConvertToText($ext)) {
			$content = Conversion::fileToText($this, $content);
		}
		else {
			$content = null;
		}

		// Only index valid UTF-8
		if (isset($content) && preg_match('//u', $content)) {
			// Truncate text at 500KB
			$content = substr(trim($content), 0, 500*1024);
		}
		else {
			$content = null;
		}

		if (null === $content && null === $title) {
			// This is already the same as what has been inserted by SQLite
			return;
		}

		$db = DB::getInstance();
		$db->preparedQuery('REPLACE INTO files_search (docid, path, title, content) VALUES (?, ?, ?, ?);',
			$this->id(), $this->path, $title ?? $this->name, $content);
	}

	/**
	 * Returns true if this is a vector or bitmap image
	 * as 'image' property is only for bitmaps
	 * @return boolean
	 */
	public function isImage(): bool
	{
		if ($this->image || $this->mime == 'image/svg+xml') {
			return true;
		}

		return false;
	}

	public function isDir(): bool
	{
		return $this->type == self::TYPE_DIRECTORY;
	}

	public function iconShape(): ?string
	{
		if ($this->isImage()) {
			return 'image';
		}
		elseif ($this->isDir()) {
			return 'directory';
		}

		return Files::getIconShape($this->name);
	}

	/**
	 * Full URL with https://...
	 */
	public function url(bool $download = false): string
	{
		$base = in_array($this->context(), [self::CONTEXT_WEB, self::CONTEXT_MODULES, self::CONTEXT_CONFIG]) ? WWW_URL : BASE_URL;
		$url = $base . $this->uri();

		if ($download) {
			$url .= '?download';
		}

		return $url;
	}

	/**
	 * Returns local URI, eg. user/1245/file.jpg
	 */
	public function uri(): string
	{
		if ($this->context() == self::CONTEXT_WEB) {
			if ($this->isDir()) {
				$parts = [$this->name];
			}
			else {
				$parts = [Utils::basename($this->parent), $this->name];
			}
		}
		else {
			$parts = explode('/', $this->path);
		}

		$parts = array_map('rawurlencode', $parts);

		return implode('/', $parts);
	}

	/**
	 * Return a HTML link to the file
	 */
	public function link(Session $session, ?string $thumb = null, bool $allow_edit = false, ?string $url = null): string
	{
		if ($thumb === 'auto') {
			$thumb = '150px';
		}

		if ($thumb && !$this->hasThumbnail()) {
			$thumb = 'icon';
		}

		if ($thumb === 'icon') {
			$label = CommonFunctions::icon(['shape' => $this->iconShape()]);
		}
		elseif ($thumb) {
			$label = sprintf('<img src="%s" alt="%s" onerror="this.classList.add(\'broken\');" />', htmlspecialchars($this->thumb_url($thumb)), htmlspecialchars($this->name));
		}
		else {
			$label = preg_replace('/[_.-]/', '&shy;$0', htmlspecialchars($this->name));
		}

		$editor = $this->editorType();

		if ($url) {
			$attrs = sprintf('href="%s"', Utils::getLocalURL($url));
		}
		elseif ($editor && ($allow_edit || $editor == 'wopi') && $this->canWrite($session)) {
			$attrs = sprintf('href="%s" target="_dialog" data-dialog-class="fullscreen" data-caption="%s"',
				Utils::getLocalURL('!common/files/edit.php?p=') . rawurlencode($this->path),
				htmlspecialchars($this->name)
			);
		}
		elseif ($this->canPreview()) {
			$attrs = sprintf('href="%s" target="_dialog" data-mime="%s" data-caption="%s"',
				$this->getPreviewURL(),
				$this->mime,
				htmlspecialchars($this->name)
			);
		}
		else {
			$attrs = sprintf('href="%s" target="_blank"', $this->url(true));
		}

		return sprintf('<a %s>%s</a>', $attrs, $label);
	}

	public function canEdit(): bool
	{
		return $this->editorType() !== null;
	}

	public function canEditInShare(): bool
	{
		return $this->editorType() === 'wopi';
	}

	public function getPreviewURL(): string
	{
		return $this->isImage() ? $this->url() : Utils::getLocalURL('!common/files/preview.php?p=') . rawurlencode($this->path);
	}

	public function previewHTML(?string $url = null, ?Session $session = null): ?string
	{
		$url ??= $this->url();

		if ($this->image) {
			return sprintf('<img src="%s" alt="%s" />', $url, htmlspecialchars($this->name));
		}
		elseif ($this->mime && ($this->mime === 'application/ogg' || strpos($this->mime, 'video/') === 0)) {
			return sprintf('<video draggable="false" autoplay="false" controls="true" src="%s" preload="metadata" />', $url);
		}
		elseif ($this->mime && strpos($this->mime, 'audio/') === 0) {
			return sprintf('<audio draggable="false" autoplay="false" controls="true" src="%s" />', $url);
		}
		elseif ($this->renderFormat()) {
			return $this->render();
		}
		else if ($html = $this->getWOPIEditorHTML($session, true, true)) {
			return $html;
		}
		else {
			return sprintf('<iframe src="%s?preview"></iframe>', $url);
		}
	}

	public function editorHTML(?Session $session = null): ?string
	{
		if ($html = $this->getWOPIEditorHTML($session, false, true)) {
			return $html;
		}

		return null;
	}

	public function preview(?Session $session = null): void
	{
		if (!$this->canPreview()) {
			throw new UserException('This file cannot be previewed');
		}

		if ($this->renderFormat()) {
			$tpl = Template::getInstance();
			$tpl->assign('content', $this->render());
			$tpl->assign('file', $this);
			$tpl->display('common/files/_preview.tpl');
		}
		else if ($html = $this->getWOPIEditorHTML($session, true)) {
			echo $html;
		}
		else {
			// We don't need $session here as read access is already checked before
			$this->serve();
		}
	}

	public function editor(?string $content = null, ?Session $session = null): bool
	{
		$editor = $this->editorType() ?? 'code';
		$csrf_key = 'edit_file_' . $this->pathHash();
		$form = new Form;
		$done = false;
		$file = $this;

		$form->runIf('content', function () use ($file, &$done) {
			$file->setContent($_POST['content'] ?? null);
			$done = true;
		}, $csrf_key);

		if ($done) {
			return true;
		}

		$tpl = Template::getInstance();
		$tpl->assign(compact('csrf_key', 'file'));

		if ($editor == 'wopi') {
			echo $this->getWOPIEditorHTML($session, false);
			return false;
		}

		$content ??= $this->fetch();
		$path = $this->path;
		$format = $this->renderFormat();
		$tpl->assign(compact('csrf_key', 'content', 'path', 'format'));
		$tpl->display(sprintf('common/files/edit_%s.tpl', $editor));
		return false;
	}

	/**
	 * Envoie le fichier au client HTTP
	 */
	public function serve($download = null): void
	{
		// Only simple files can be served, not directories
		if ($this->type != self::TYPE_FILE) {
			header('HTTP/1.1 404 Not Found', true, 404);
			throw new UserException('Page non trouvée', 404);
		}

		$this->_serve(null, $download);

		if (($path = $this->getLocalFilePath()) && in_array($this->context(), [self::CONTEXT_WEB, self::CONTEXT_CONFIG])) {
			Web_Cache::link($this->uri(), $path);
		}
	}

	protected function _serve(?string $path = null, $download = null): void
	{
		$is_versioned_url = !empty($_GET['h']) && $_GET['h'] === $this->getShortEtag();

		Utils::HTTPCache($this->etag(), $this->modified->getTimestamp(), 24*3600, $is_versioned_url);
		header('X-Powered-By: Paheko/PHP');

		// Security: disable running scripts from SVG images and HTML documents
		if (strpos($this->mime, 'svg') !== false
			|| stripos($this->name, '.svg') !== false
			|| strpos($this->mime, 'html') !== false
			|| stripos($this->name, '.htm') !== false) {
			header('Content-Security-Policy: script-src ;');
		}

		if (null === $path) {
			$type = $this->mime;

			// Force CSS mimetype
			if (substr($this->name, -4) == '.css') {
				$type = 'text/css';
			}
			elseif (substr($this->name, -3) == '.js') {
				$type = 'text/javascript';
			}

			if (substr($type, 0, 5) == 'text/') {
				$type .= ';charset=utf-8';
			}

			header(sprintf('Content-Type: %s', $type));
			header(sprintf('Content-Disposition: %s; filename="%s"', $download ? 'attachment' : 'inline', is_string($download) ? $download : $this->name));
		}
		elseif (!file_exists($path)) {
			throw new UserException('Le contenu du fichier est introuvable.');
		}

		$path ??= $this->getLocalFilePath();

		$size = $path ? filesize($path) : $this->size;

		if (null === $path) {
			$pointer = $this->getReadOnlyPointer();
		}
		else {
			$pointer = fopen($path, 'rb');
		}

		if (!$pointer) {
			header('HTTP/1.1 404 Not Found', true, 404);
			header('Content-Type: text/html', true);
			header_remove('Content-Disposition');
			throw new UserException('Le contenu de ce fichier est introuvable', 404);
		}

		try {
			Server::serveFile(null, null, $pointer, [
				'xsendfile' => ENABLE_XSENDFILE,
				'ranges'    => true,
				'gzip'      => true,
				'name'      => $this->name,
				'size'      => $size,
			]);
		}
		catch (\LogicException $e) {
			throw new UserException($e->getMessage(), $e->getCode());
		}
		finally {
			fclose($pointer);
		}
	}

	public function fetch()
	{
		if ($this->type == self::TYPE_DIRECTORY) {
			throw new \LogicException('Cannot fetch a directory');
		}

		$p = $this->getReadOnlyPointer();

		if (null === $p) {
			$path = Files::callStorage('getLocalFilePath', $this);

			if (!$path || !file_exists($path)) {
				return '';
			}

			return file_get_contents($path);
		}

		$out = '';

		while (!feof($p)) {
			$out .= fread($p, 8192);
		}

		fclose($p);
		return $out;
	}

	public function render(?string $user_prefix = null)
	{
		$editor_type = $this->renderFormat();

		if ($editor_type == 'skriv' || $editor_type == 'markdown') {
			return Render::render($editor_type, $this->path, $this->fetch(), $user_prefix);
		}
		elseif ($editor_type == 'text') {
			return sprintf('<pre>%s</pre>', htmlspecialchars($this->fetch()));
		}
		else {
			throw new \LogicException('Cannot render file of this type');
		}
	}

	public function pathHash(): string
	{
		return sha1($this->path);
	}

	public function isPublic(): bool
	{
		$context = $this->context();

		if ($context == self::CONTEXT_MODULES || $context == self::CONTEXT_WEB) {
			return true;
		}

		if ($context == self::CONTEXT_CONFIG) {
			$file = array_search($this->path, Config::FILES);

			if ($file && in_array($file, Config::FILES_PUBLIC)) {
				return true;
			}
		}

		return false;
	}

	public function path_uri(): string
	{
		return rawurlencode($this->path);
	}

	public function parent_uri(): string
	{
		return $this->parent ? rawurlencode($this->parent) : '';
	}

	public function getFormatDescription(): string
	{
		switch ($this->extension()) {
			case 'odt': return 'Document LibreOffice';
			case 'ods': return 'Tableur LibreOffice';
			case 'odp': return 'Présentation LibreOffice';
			case 'odg': return 'Dessin LibreOffice';
			case 'doc':
			case 'docx': return 'Document Microsoft Office';
			case 'xls':
			case 'xlsx': return 'Tableur Microsoft Office';
			case 'ppt':
			case 'pptx': return 'Présentation Microsoft Office';
			case 'pdf': return 'Document PDF';
			case 'png':
			case 'webp':
			case 'jpeg':
			case 'jpg':
			case 'gif':
				return 'Image';
			case 'epub':
			case 'mobi':
				return 'Livre électronique';
			case 'md': return 'Texte MarkDown';
			case 'txt': return 'Texte';
			case 'mp3':
			case 'ogg':
			case 'aac':
			case 'flac':
			case 'opus':
			case 'wav':
			case 'wma':
				return 'Fichier audio';
			case 'mkv':
			case 'mp4':
			case 'avi':
			case 'mov':
			case 'webm':
				return 'Fichier vidéo';
			default: return 'Fichier';
		}
	}

	public function extension(): ?string
	{
		$pos = strrpos($this->name, '.');

		if (false === $pos) {
			return null;
		}

		return strtolower(substr($this->name, $pos+1));
	}

	static public function filterName(string $name): string
	{
		foreach (self::FORBIDDEN_CHARACTERS as $char) {
			$name = str_replace($char, '', $name);
		}

		return $name;
	}

	static public function validateFileName(string $name): void
	{
		if (0 === strpos($name, '.ht') || $name == '.user.ini') {
			throw new ValidationException('Nom de fichier invalide');
		}

		if (strlen($name) > 250) {
			throw new ValidationException('Nom de fichier trop long');
		}

		if (strlen($name) < 1) {
			throw new ValidationException('Nom de fichier trop court');
		}

		foreach (self::FORBIDDEN_CHARACTERS as $char) {
			if (strpos($name, $char) !== false) {
				throw new ValidationException('Nom de fichier invalide, le caractère suivant est interdit : ' . $char);
			}
		}

		$extension = strtolower(substr($name, strrpos($name, '.')+1));

		if (preg_match(self::FORBIDDEN_EXTENSIONS, $extension)) {
			throw new ValidationException(sprintf('Extension de fichier "%s" non autorisée, merci de renommer le fichier avant envoi.', $extension));
		}
	}

	static public function validatePath(string $path): array
	{
		if (false !== strpos($path, '..')) {
			throw new ValidationException('Chemin invalide: ' . $path);
		}

		$parts = explode('/', trim($path, '/'));

		if (count($parts) < 1) {
			throw new ValidationException('Chemin invalide: ' . $path);
		}

		$context = array_shift($parts);

		if (!array_key_exists($context, self::CONTEXTS_NAMES)) {
			throw new ValidationException('Contexte invalide: ' . $context);
		}

		$name = array_pop($parts);
		$ref = implode('/', $parts);
		return [$context, $ref ?: null, $name];
	}

	/**
	 * Only admins can create or rename files to .html / .js
	 * This is to avoid XSS attacks from a non-admin user
	 */
	static public function validateCanHTML(string $name, string $path, ?Session $session = null): void
	{
		// If no session was given, the file creation / rename comes from a plugin
		// probably, so it's always allowed to create HTML
		if (null === $session) {
			return;
		}

		if (!preg_match('/\.(?:htm|js|xhtm)/', $name)) {
			return;
		}

		// Web module allows web admin to create HTML files
		if (0 === strpos($path, self::CONTEXT_MODULES . '/web')
			&& $session->canAccess($session::SECTION_WEB, $session::ACCESS_ADMIN)) {
			return;
		}

		if ($session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)) {
			return;
		}

		throw new ValidationException('Seuls les administrateurs peuvent créer des fichiers de ce type.');
	}

	public function renderFormat(): ?string
	{
		if (substr($this->name, -6) == '.skriv') {
			$format = Render::FORMAT_SKRIV;
		}
		elseif (substr($this->name, -3) == '.md') {
			$format = Render::FORMAT_MARKDOWN;
		}
		elseif ($this->mime && substr($this->mime, 0, 5) == 'text/' && $this->mime != 'text/html') {
			$format = 'text';
		}
		else {
			$format = null;
		}

		return $format;
	}

	public function editorType(): ?string
	{
		static $text_extensions = ['css', 'txt', 'xml', 'html', 'htm', 'tpl', 'ini'];

		$ext = $this->extension();

		$format = $this->renderFormat();

		if ($format == Render::FORMAT_SKRIV || $format == Render::FORMAT_MARKDOWN) {
			return 'web';
		}
		elseif ($format == 'text' || in_array($ext, $text_extensions)) {
			return 'code';
		}
		elseif (!WOPI_DISCOVERY_URL) {
			return null;
		}

		if ($this->getWopiURL('edit')) {
			return 'wopi';
		}

		return null;
	}

	public function export(): array
	{
		return $this->asArray(true) + ['url' => $this->url()];
	}

	public function touch($date = null): void
	{
		if (null === $date) {
			$date = new \DateTime;
		}
		elseif (!($date instanceof \DateTimeInterface) && ctype_digit($date)) {
			$date = new \DateTime('@' . $date);
		}
		elseif (!($date instanceof \DateTimeInterface)) {
			throw new \InvalidArgumentException('Invalid date string: ' . $date);
		}

		Files::assertStorageIsUnlocked();
		Files::callStorage('touch', $this, $date);
		$this->set('modified', $date);
		$this->save();
	}

	public function getReadOnlyPointer()
	{
		return Files::callStorage('getReadOnlyPointer', $this);
	}

	public function iterate(): \Generator
	{
		yield $this;

		if (!$this->isDir()) {
			return;
		}

		$db = DB::getInstance();
		yield from EntityManager::getInstance(self::class)->iterate('SELECT * FROM files WHERE parent = ? ORDER BY type DESC, name COLLATE NOCASE ASC;', $this->path);
	}

	public function iterateRecursive(): \Generator
	{
		yield $this;

		if (!$this->isDir()) {
			return;
		}

		$db = DB::getInstance();
		yield from EntityManager::getInstance(self::class)->iterate('SELECT * FROM files WHERE parent = ? OR parent LIKE ? ESCAPE \'!\' ORDER BY type DESC, name COLLATE NOCASE ASC;', $this->path, $db->escapeLike($this->path, '!') . '/%');
	}

	public function getRecursiveSize(): int
	{
		if ($this->type == self::TYPE_FILE) {
			return $this->size;
		}

		$db = DB::getInstance();
		return $db->firstColumn('SELECT SUM(size) FROM files
			WHERE type = ? AND path LIKE ? ESCAPE \'!\';',
			File::TYPE_FILE,
			$db->escapeLike($this->path, '!') . '/%'
		) ?: 0;
	}

	public function getRecursiveLastModified(): int
	{
		if ($this->type === self::TYPE_FILE) {
			return $this->modified->getTimestamp();
		}

		$db = DB::getInstance();
		return $db->firstColumn('SELECT strftime(\'%s\', MAX(modified)) FROM files
			WHERE type = ? AND path LIKE ? ESCAPE \'!\';',
			File::TYPE_FILE,
			$db->escapeLike($this->path, '!') . '/%'
		) ?: 0;
	}

	public function getParentHashID(): string
	{
		return $this->parent()->hash_id;
	}

	public function webdav_root_url(): string
	{
		return BASE_URL . 'dav/' . $this->context() . '/';
	}

	public function mkdir(string $name, ?Session $session): File
	{
		if (!$this->isDir()) {
			throw new \LogicException('Cannot create a directory inside a file');
		}

		$name = trim($name);

		if (substr_count($name, '/') !== 0) {
			throw new UserException('Le nom du répertoire ne peut pas contenir de "/"');
		}

		$path = $this->path . '/' . $name;

		if (null !== $session && !$this->canCreateDir($path, $session)) {
			throw new \LogicException('Cannot create a directory here');
		}

		return Files::mkdir($path);
	}
}
