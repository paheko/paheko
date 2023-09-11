<?php

namespace Paheko\Files\WebDAV;

use KD2\WebDAV\AbstractStorage;
use KD2\WebDAV\WOPI;
use KD2\WebDAV\Exception as WebDAV_Exception;

use Paheko\DB;
use Paheko\Utils;
use Paheko\ValidationException;
use Paheko\Users\Session as UserSession;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Web\Router;

use const Paheko\{FILE_STORAGE_BACKEND, SECRET_KEY, WWW_URL};

class Storage extends AbstractStorage
{
	/**
	 * These file names will be ignored when doing a PUT
	 * as they are garbage, coming from some OS
	 */
	const PUT_IGNORE_PATTERN = '!^~(?:lock\.|^\._)|^(?:\.DS_Store|Thumbs\.db|desktop\.ini)$!';

	protected ?array $cache = null;
	protected array $root = [];

	protected ?NextCloud $nextcloud;
	protected UserSession $session;

	public function __construct(UserSession $session, ?NextCloud $nextcloud = null)
	{
		$this->session = $session;
		$this->nextcloud = $nextcloud;
	}

	protected function populateRootCache(): void
	{
		if (isset($this->cache)) {
			return;
		}

		$access = Files::listReadAccessContexts($this->session);

		$this->cache = ['' => Files::get('')];

		foreach ($access as $context => $name) {
			$this->cache[$context] = Files::get($context);
			$this->root[] = $context;
		}
	}

	protected function load(string $uri)
	{
		$this->populateRootCache();

		$uri = $uri ?: null;

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
	public function list(string $uri, ?array $properties): iterable
	{
		$this->populateRootCache();

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

		if ($file->type != $file::TYPE_DIRECTORY) {
			return;
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
		$file = $this->load($uri);

		if (!$file) {
			throw new WebDAV_Exception('File Not Found', 404);
		}

		if (!$file->canRead($this->session)) {
			throw new WebDAV_Exception('Vous n\'avez pas accès à ce chemin', 403);
		}

		$type = $file->type;

		// Serve files
		if ($type == File::TYPE_DIRECTORY) {
			return null;
		}

		$path = $file->getLocalFilePath();

		if ($path && Router::isXSendFileEnabled()) {
			Router::xSendFile($path);
			return ['stop' => true];
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
		$this->populateRootCache();

		if (isset($this->cache[$uri])) {
			return true;
		}

		return Files::exists($uri);
	}

	protected function get_file_property(string $uri, string $name, int $depth)
	{
		$file = $this->load($uri);
		$is_dir = $file->type == File::TYPE_DIRECTORY;

		if (!$file) {
			throw new \LogicException('File does not exist');
		}

		switch ($name) {
			case 'DAV::getcontentlength':
				return $is_dir ? null : $file->size;
			case 'DAV::getcontenttype':
				return $is_dir ? null : $file->mime;
			case 'DAV::resourcetype':
				return $is_dir ? 'collection' : '';
			case 'DAV::getlastmodified':
				return $file->modified ?? null;
			case 'DAV::displayname':
				return $file->name;
			case 'DAV::ishidden':
				return false;
			case 'DAV::getetag':
				return $file->etag();
			case 'DAV::lastaccessed':
				return null;
			case 'DAV::creationdate':
				return $file->modified ?? null;
			case WebDAV::PROP_DIGEST_MD5:
				if ($file->type != File::TYPE_FILE) {
					return null;
				}

				return $file->md5 ?? null;
			// NextCloud stuff
			case NextCloud::PROP_NC_HAS_PREVIEW:
				return $file->image ? 'true' : 'false';
			case NextCloud::PROP_NC_IS_ENCRYPTED:
				return 'false';
			case NextCloud::PROP_OC_SHARETYPES:
				return WebDAV::EMPTY_PROP_VALUE;
			case NextCloud::PROP_OC_DOWNLOADURL:
				return $this->nextcloud->getDirectDownloadURL($uri, $this->session::getUserId());
			case Nextcloud::PROP_NC_RICH_WORKSPACE:
				return '';
			case NextCloud::PROP_OC_ID:
				// fileId is required by NextCloud desktop client
				return $file->id();
			case NextCloud::PROP_OC_PERMISSIONS:
				$permissions = [
					NextCloud::PERM_READ => $file->canRead($this->session),
					NextCloud::PERM_WRITE => $file->canWrite($this->session),
					NextCloud::PERM_DELETE => $file->canDelete($this->session),
					NextCloud::PERM_RENAME => $file->canRename($this->session),
					NextCloud::PERM_MOVE => $file->canRename($this->session),
					NextCloud::PERM_CREATE_FILES_DIRS => $file->canCreateHere($this->session),
				];

				$permissions = array_filter($permissions, fn($a) => $a);
				return implode('', array_keys($permissions));
			case 'DAV::quota-available-bytes':
				return Files::getRemainingQuota();
			case 'DAV::quota-used-bytes':
				return Files::getUsedQuota();
			case Nextcloud::PROP_OC_SIZE:
				return $file->getRecursiveSize();
			case WOPI::PROP_USER_NAME:
				return $this->session->getUser()->name();
			case WOPI::PROP_USER_ID:
				return $this->session->getUser()->id;
			case WOPI::PROP_READ_ONLY:
				return $file->canWrite($this->session) ? false : true;
			case WOPI::PROP_FILE_URL:
				$id = gzcompress($uri);
				$id = WOPI::base64_encode_url_safe($id);
				return WWW_URL . 'wopi/files/' . $id;
			default:
				break;
		}

		return null;
	}

	/**
	 * @extends
	 */
	public function properties(string $uri, ?array $properties, int $depth): ?array
	{
		$this->populateRootCache();
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

	public function put(string $uri, $pointer, ?string $hash_algo, ?string $hash, ?int $mtime): bool
	{
		if (!strpos($uri, '/')) {
			throw new WebDAV_Exception('Impossible de créer un fichier ici', 403);
		}

		if (preg_match(self::PUT_IGNORE_PATTERN, basename($uri))) {
			return false;
		}

		$target = Files::get($uri);

		if ($target && $target->type === $target::TYPE_DIRECTORY) {
			throw new WebDAV_Exception('Target is a directory', 409);
		}

		$new = !$target ? true : false;

		if ($new && !File::canCreate($uri, $this->session)) {
			throw new WebDAV_Exception('Vous n\'avez pas l\'autorisation de créer ce fichier', 403);
		}
		elseif (!$new && !$target->canWrite($this->session)) {
			throw new WebDAV_Exception('Vous n\'avez pas l\'autorisation de modifier ce fichier', 403);
		}

		$h = $hash ? hash_init($hash_algo == 'MD5' ? 'md5' : 'sha1') : null;

		while (!feof($pointer)) {
			if ($h) {
				hash_update($h, fread($pointer, 8192));
			}
			else {
				fread($pointer, 8192);
			}
		}

		if ($h) {
			if (hash_final($h) != $hash) {
				throw new WebDAV_Exception('The data sent does not match the supplied hash', 400);
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
		if (!strpos($uri, '/')) {
			throw new WebDAV_Exception('Ce répertoire ne peut être supprimé', 403);
		}

		$target = Files::get($uri);

		if (!$target) {
			throw new WebDAV_Exception('This file does not exist', 404);
		}

		if (!$target->canDelete($this->session)) {
			throw new WebDAV_Exception('Vous n\'avez pas l\'autorisation de supprimer ce fichier', 403);
		}

		$target->moveToTrash();
	}

	protected function copymove(bool $move, string $uri, string $destination): bool
	{
		if (!strpos($uri, '/')) {
			throw new WebDAV_Exception('Ce répertoire ne peut être modifié', 403);
		}

		$source = Files::get($uri);

		if (!$source) {
			throw new WebDAV_Exception('File not found', 404);
		}

		if (!$source->canMoveTo($destination, $this->session)) {
			throw new WebDAV_Exception('Vous n\'avez pas l\'autorisation de déplacer ce fichier', 403);
		}

		if (!$move) {
			if ($source->size > Files::getRemainingQuota()) {
				throw new WebDAV_Exception('Your quota is exhausted', 403);
			}
		}

		$overwritten = Files::exists($destination);

		if ($overwritten) {
			$this->delete($destination);
		}

		$method = $move ? 'rename' : 'copy';

		$source->$method($destination);

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
		if (!strpos($uri, '/')) {
			throw new WebDAV_Exception('Impossible de créer un répertoire ici', 403);
		}

		if (!File::canCreateDir($uri)) {
			throw new WebDAV_Exception('Vous n\'avez pas l\'autorisation de créer un répertoire ici', 403);
		}

		if (Files::exists($uri)) {
			throw new WebDAV_Exception('There is already a file with that name', 405);
		}

		if (!Files::exists(Utils::dirname($uri))) {
			throw new WebDAV_Exception('The parent directory does not exist', 409);
		}

		Files::mkdir($uri);
	}

	protected function createWopiToken(string $uri)
	{
		$ttl = time()+(3600*10);
		$session_id = $this->session->id();
		$hash = WebDAV::hmac(compact('uri', 'ttl', 'session_id'), SECRET_KEY);
		$data = sprintf('%s_%s_%s', $hash, $session_id, $ttl);

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
		$hash = strtok($token_decode, '_');
		$session_id = strtok('_');
		$ttl = (int) strtok(false);
		$check = WebDAV::hmac(compact('uri', 'ttl', 'session_id'), SECRET_KEY);

		if (!hash_equals($hash, $check)) {
			return null;
		}

		if ($ttl < time()) {
			return null;
		}

		$this->session->setId($session_id);
		$this->session->start(true);

		if (!$this->session->isLogged()) {
			return null;
		}

		return $uri;
	}
}
