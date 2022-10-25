<?php

namespace Garradin\Files\WebDAV;

use KD2\WebDAV\AbstractStorage;
use KD2\WebDAV\WOPI;
use KD2\WebDAV\Exception as WebDAV_Exception;

use Garradin\DB;
use Garradin\Utils;
use Garradin\ValidationException;

use Garradin\Users\Session;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;

class Storage extends AbstractStorage
{
	/**
	 * These file names will be ignored when doing a PUT
	 * as they are garbage, coming from some OS
	 */
	const PUT_IGNORE_PATTERN = '!^~(?:lock\.|^\._)|^(?:\.DS_Store|Thumbs\.db|desktop\.ini)$!';

	protected $cache = [];
	protected $root = [];

	public function __construct()
	{
		$access = Files::listReadAccessContexts(Session::getInstance());

		$this->cache[''] = (object) ['name' => '', 'type' => File::TYPE_DIRECTORY];

		foreach ($access as $context => $name) {
			$this->cache[$context] = (object) ['name' => $context, 'type' => File::TYPE_DIRECTORY];
			$this->root[] = $context;
		}
	}

	protected function load(string $uri)
	{
		if (!isset($this->cache[$uri])) {
			$this->cache[$uri] = Files::get($uri);

			if (!$this->cache[$uri]) {
				return null;
			}
		}

		return $this->cache[$uri];
	}

	/**
	 * @extends
	 */
	public function getLock(string $uri, ?string $token = null): ?string
	{
		// It is important to check also for a lock on parent directory as we support depth=1
		$sql = 'SELECT scope FROM files_webdav_locks WHERE (uri = ? OR uri = ?)';
		$params = [$uri, Utils::dirname($uri)];

		if ($token) {
			$sql .= ' AND token = ?';
			$params[] = $token;
		}

		$sql .= ' LIMIT 1';

		return DB::getInstance()->firstColumn($sql, ...$params);
	}

	/**
	 * @extends
	 */
	public function lock(string $uri, string $token, string $scope): void
	{
		DB::getInstance()->preparedQuery('REPLACE INTO files_webdav_locks VALUES (?, ?, ?, datetime(\'now\', \'+5 minutes\'));', $uri, $token, $scope);
	}

	/**
	 * @extends
	 */
	public function unlock(string $uri, string $token): void
	{
		DB::getInstance()->preparedQuery('DELETE FROM files_webdav_locks WHERE uri = ? AND token = ?;', $uri, $token);
	}

	/**
	 * @extends
	 */
	public function list(string $uri, ?array $properties): iterable
	{
		if (!$uri) {
			foreach ($this->root as $name) {
				yield $name => null;
			}
			return;
		}

		$file = $this->load($uri);

		if (!$file) {
			return null;
		}

		foreach (Files::list($uri) as $file) {
			$path = $uri . '/' . $file->name;
			$this->cache[$path] = $file;
			yield $file->name => null;
		}
	}

	/**
	 * @extends
	 */
	public function get(string $uri): ?array
	{
		$session = Session::getInstance();

		$file = Files::get($uri);

		if (!$file) {
			throw new WebDAV_Exception('File Not Found', 404);
		}

		if (!$file->checkReadAccess($session)) {
			throw new WebDAV_Exception('Vous n\'avez pas accès à ce chemin', 403);
		}

		$type = $file->type;

		// Serve files
		if ($type == File::TYPE_DIRECTORY) {
			return null;
		}

		// We trust the WebDAV server to be more efficient that File::serve
		// with serving a file for WebDAV clients
		return ['resource' => $file->getReadOnlyPointer()];
	}

	/**
	 * @extends
	 */
	public function exists(string $uri): bool
	{
		if (isset($this->cache[$uri])) {
			return true;
		}

		return Files::exists($uri);
	}

	protected function get_file_property(string $uri, string $name, int $depth)
	{
		$file = $this->load($uri);

		if (!$file) {
			throw new \LogicException('File does not exist');
		}

		$session = Session::getInstance();

		switch ($name) {
			case 'DAV::getcontentlength':
				return $file->type == File::TYPE_DIRECTORY ? null : $file->size;
			case 'DAV::getcontenttype':
				// ownCloud app crashes if mimetype is provided for a directory
				// https://github.com/owncloud/android/issues/3768
				return $file->type == File::TYPE_DIRECTORY ? null : $file->mime;
			case 'DAV::resourcetype':
				return $file->type == File::TYPE_DIRECTORY ? 'collection' : '';
			case 'DAV::getlastmodified':
				return $file->modified ?? null;
			case 'DAV::displayname':
				return $file->name;
			case 'DAV::ishidden':
				return false;
			case 'DAV::getetag':
				return $file instanceof File ? $file->etag() : null;
			case 'DAV::lastaccessed':
				return null;
			case 'DAV::creationdate':
				return $file->modified ?? null;
			case WebDAV::PROP_DIGEST_MD5:
				if ($file->type != File::TYPE_FILE) {
					return null;
				}

				return md5_file($file->fullpath());
			// NextCloud stuff
			case NextCloud::PROP_NC_HAS_PREVIEW:
			case NextCloud::PROP_NC_IS_ENCRYPTED:
				return 'false';
			case NextCloud::PROP_OC_SHARETYPES:
				return WebDAV::EMPTY_PROP_VALUE;
			case NextCloud::PROP_OC_DOWNLOADURL:
				return NextCloud::getDirectURL($uri, $this->users->current()->login);
			case Nextcloud::PROP_NC_RICH_WORKSPACE:
				return '';
			case NextCloud::PROP_OC_ID:
				return NextCloud::getDirectID('', $uri);
			case NextCloud::PROP_OC_PERMISSIONS:
				$write = $file->checkWriteAccess($session);
				$delete = $file->checkDeleteAccess($session);

				$permissions = [
					NextCloud::PERM_READ => $file->checkReadAccess($session),
					NextCloud::PERM_WRITE => $file->checkWriteAccess($session),
					NextCloud::PERM_DELETE => $file->checkDeleteAccess($session),
					NextCloud::PERM_RENAME_MOVE => $write && $delete,
					NextCloud::PERM_CREATE => File::checkCreateAccess($uri, $session),
				];

				$permissions = array_filter($permissions, fn($a) => $a);
				return implode('', array_values($permissions));
			case 'DAV::quota-available-bytes':
				return Files::getRemainingQuota();
			case 'DAV::quota-used-bytes':
				return Files::getUsedQuota();
			case Nextcloud::PROP_OC_SIZE:
				return $file->size;
			case WOPI::PROP_USER_NAME:
				return $session->getUser()->name();
			case WOPI::PROP_READ_ONLY:
				return $file->checkWriteAccess($session) ? false : true;
			case WOPI::PROP_FILE_URL:
				$id = gzcompress($uri);
				$id = WOPI::base64_encode_url_safe($id);
				return WWW_URL . 'wopi/files/' . $id;
			default:
				break;
		}

		if (in_array($name, NextCloud::NC_PROPERTIES) || in_array($name, WebDAV::BASIC_PROPERTIES) || in_array($name, WebDAV::EXTENDED_PROPERTIES)) {
			return null;
		}

		return $this->getCustomProperty($uri, $name);
	}

	protected function getCustomProperty(string $uri, string $name)
	{
		return DB::getInstance()->first('SELECT * FROM files_webdav_properties WHERE uri = ? AND name = ?;', $uri, $name);
	}

	/**
	 * @extends
	 */
	public function properties(string $uri, ?array $properties, int $depth): ?array
	{
		$file = $this->load($uri);

		if (!$file) {
			return null;
		}

		if (null === $properties) {
			$properties = array_merge(WebDAV::BASIC_PROPERTIES, ['DAV::getetag', Nextcloud::PROP_OC_ID]);
		}

		$out = [];

		// Generate a new token for WOPI, and provide also TTL
		if (in_array(WOPI::PROP_TOKEN, $properties)) {
			$out = $this->createWopiToken($uri);
			unset($properties[WOPI::PROP_TOKEN], $properties[WOPI::PROP_TOKEN_TTL]);
		}

		foreach ($properties as $name) {
			$v = $this->get_file_property($uri, $name, $depth);

			if (null !== $v) {
				$out[$name] = $v;
			}
		}

		return $out;
	}

	public function put(string $uri, $pointer, ?string $hash, ?int $mtime): bool
	{
		if (preg_match(self::PUT_IGNORE_PATTERN, basename($uri))) {
			return false;
		}

		$target = Files::get($uri);

		if ($target && $target->type === $target::TYPE_DIRECTORY) {
			throw new WebDAV_Exception('Target is a directory', 409);
		}

		$new = !$target ? true : false;
		$session = Session::getInstance();

		if ($new && !File::checkCreateAccess($uri, $session)) {
			throw new WebDAV_Exception('Cannot create here', 403);
		}
		elseif (!$new && $target->checkWriteAccess($session)) {
			throw new WebDAV_Exception('You cannot write to this file', 403);
		}

		$h = $hash ? hash_init('md5') : null;

		while (!feof($fp)) {
			if ($h) {
				hash_update($h, fread($pointer, 8192));
			}
			else {
				fread($pointer, 8192);
			}
		}

		if ($h) {
			if (hash_final($h) != $hash) {
				throw new WebDAV_Exception('The data sent does not match the supplied MD5 hash', 400);
			}
		}

		// Check size
		$size = ftell($pointer);

		try {
			Files::checkQuota($size);
		}
		catch (ValidationException $e) {
			throw new WebDAV_Exception($e->getMessage(), 403);
		}

		rewind($pointer);

		if ($new) {
			$target = Files::createFromPointer($uri, $pointer);
		}
		else {
			$target->store(compact('pointer'));
		}

		if ($mtime) {
			$target->touch(new \DateTime('@' . $mtime));
		}

		return $new;
	}

	/**
	 * @extends
	 */
	public function delete(string $uri): void
	{
		$target = Files::get($uri);

		if (!$target) {
			throw new WebDAV_Exception('Target does not exist', 404);
		}

		$target->delete();
		DB::getInstance()->preparedQuery('DELETE FROM files_webdav_properties WHERE uri = %d;', [$uri]);
	}

	protected function copymove(bool $move, string $uri, string $destination): bool
	{
		$source = Files::get($uri);

		if (!$source) {
			throw new WebDAV_Exception('File not found', 404);
		}

		$parent = Files::get($source->parent);

		if (!$parent || !$parent->type != $parent::TYPE_DIRECTORY) {
			throw new WebDAV_Exception('Target parent directory does not exist', 409);
		}

		if (!$move) {
			if ($source->size > Files::getRemainingQuota(true)) {
				throw new WebDAV_Exception('Your quota is exhausted', 403);
			}
		}

		$overwritten = Files::exists($destination);

		if ($overwritten) {
			$this->delete($destination);
		}

		$method = $move ? 'rename' : 'copy';

		$source->$method($destination);

		if ($move) {
			$db = DB::getInstance();
			$db->begin();
			$db->preparedQuery('UPDATE files_webdav_properties SET uri = ? WHERE uri = ?;', $destination, $uri);
			$db->preparedQuery('UPDATE files_webdav_properties SET uri = ? || SUBSTR(uri, ?) WHERE uri LIKE ? ESCAPE \\;',
				$destination,
				strlen($uri),
				strtr($uri, ['%' => '\\%', '_' => '\\_']) . '/%'
			);
			$db->commit();
		}

		return $overwritten;
	}

	/**
	 * @extends
	 */
	public function copy(string $uri, string $destination): bool
	{
		return $this->copymove(false, $uri, $destination);
	}

	/**
	 * @extends
	 */
	public function move(string $uri, string $destination): bool
	{
		return $this->copymove(true, $uri, $destination);
	}

	/**
	 * @extends
	 */
	public function mkcol(string $uri): void
	{
		if (Files::exists($uri)) {
			throw new WebDAV_Exception('There is already a file with that name', 405);
		}

		if (!Files::exists(Utils::dirname($uri))) {
			throw new WebDAV_Exception('The parent directory does not exist', 409);
		}

		Files::mkdir($uri);
	}

	/**
	 * @extends
	 */
	public function setProperties(string $uri, string $body): void
	{
		$properties = WebDAV::parsePropPatch($body);

		if (!count($properties)) {
			return;
		}

		$db = DB::getInstance();

		$db->begin();

		foreach ($properties as $name => $prop) {
			if ($prop['action'] == 'set') {
				$db->preparedQuery(
					'REPLACE INTO files_webdav_properties (uri, name, attributes, xml) VALUES (?, ?, ?, ?);',
					[$uri, $name, $prop['attributes'], $prop['content']]
				);
			}
			else {
				$db->preparedQuery('DELETE FROM files_webdav_properties WHERE uri = ? AND name = ?;', $uri, $name);
			}
		}

		$db->commit();

		return;
	}

	protected function createWopiToken(string $uri)
	{
		$ttl = time()+(3600*10);
		$hash = sha1(SECRET_KEY . $uri . $ttl);
		$data = sprintf('%s_%s', $hash, $ttl);

		return [
			WOPI::PROP_TOKEN => WOPI::base64_encode_url_safe($data),
			WOPI::PROP_TOKEN_TTL => $ttl * 1000,
		];
	}

	public function getWopiURI(string $id, string $token): ?string
	{
		$id = WOPI::base64_decode_url_safe($id);
		$uri = gzuncompress($id);
		$token_decode = WOPI::base64_decode_url_safe($token);
		$hash = strtok($token_decode, ':');
		$ttl = strtok(false);

		if ($hash != sha1(SECRET_KEY . $uri . $ttl)) {
			return null;
		}

		if ($ttl < time()) {
			return null;
		}

		return $uri;
	}
}
