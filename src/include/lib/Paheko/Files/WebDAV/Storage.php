<?php

namespace Paheko\Files\WebDAV;

use KD2\WebDAV\AbstractStorage;
use KD2\WebDAV\WOPI;
use KD2\WebDAV\Exception as WebDAV_Exception;

use Paheko\DB;
use Paheko\Utils;
use Paheko\ValidationException;
use Paheko\Users\Session as UserSession;
use Paheko\Users\Users;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Web\Router;

use const Paheko\{LOCAL_SECRET_KEY};

class Storage extends AbstractStorage
{
	protected ?array $cache = null;
	protected array $root = [];

	protected ?NextCloud $nextcloud;
	protected ?UserSession $session;

	public function __construct(?UserSession $session, ?NextCloud $nextcloud = null)
	{
		$this->session = $session;
		$this->nextcloud = $nextcloud;
	}

	protected function populateRootCache(): void
	{
		if (isset($this->cache) || null === $this->session) {
			return;
		}

		$access = Files::listReadAccessContexts($this->session);

		$this->cache = ['' => Files::get('')];

		foreach ($access as $context => $name) {
			$this->cache[$context] = Files::get($context);
			$this->root[] = $context;
		}
	}

	protected function load(string $uri): ?File
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
		$file = $this->getFile($uri);

		if (!$file) {
			return null;
		}

		$file->serve();
		return ['stop' => true];
	}

	/**
	 * @extends
	 */
	public function fetch(string $uri): ?string
	{
		$file = $this->getFile($uri);

		if (!$file) {
			return null;
		}

		return $file->fetch();
	}

	protected function getFile(string $uri): ?File
	{
		$file = $this->load($uri);

		if (!$file) {
			throw new WebDAV_Exception('File Not Found', 404);
		}

		if (null !== $this->session && !$file->canRead($this->session)) {
			throw new WebDAV_Exception('Vous n\'avez pas accès à ce chemin', 403);
		}

		$type = $file->type;

		// Serve files
		if ($type == File::TYPE_DIRECTORY) {
			return null;
		}

		return $file;
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
			case NextCloud::PROP_NC_RICH_WORKSPACE:
				return '';
			case NextCloud::PROP_OC_ID:
				// fileId is required by NextCloud desktop client
				if (!isset($file->id)) {
					// Root directory doesn't have a ID, give something random instead
					return 10000000;
				}

				return $file->id;
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
			case NextCloud::PROP_OC_SIZE:
				return $file->getRecursiveSize();
			default:
				break;
		}

		return null;
	}

	/**
	 * @extends
	 */
	public function propfind(string $uri, ?array $properties, int $depth): ?array
	{
		$this->populateRootCache();
		$file = $this->load($uri);

		if (!$file) {
			return null;
		}

		if (null === $properties) {
			$properties = array_merge(WebDAV::BASIC_PROPERTIES, ['DAV::getetag', NextCloud::PROP_OC_ID]);
		}

		$out = [];

		foreach ($properties as $name) {
			$v = $this->get_file_property($uri, $name, $depth);

			if (null !== $v) {
				$out[$name] = $v;
			}
		}

		return $out;
	}

	public function put(string $uri, $pointer, ?string $hash_algo, ?string $hash): bool
	{
		if (!strpos($uri, '/')) {
			throw new WebDAV_Exception('Impossible de créer un fichier ici', 403);
		}

		// Ignore temporary files
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
		elseif (!$new && null !== $this->session && !$target->canWrite($this->session)) {
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
			Files::createFromPointer($uri, $pointer, $this->session);
		}
		else {
			$target->store(compact('pointer'));
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

	/**
	 * @extends
	 */
	public function touch(string $uri, \DateTimeInterface $datetime): bool
	{
		$file = Files::get($uri);

		if (!$file) {
			return false;
		}

		$file->touch($datetime);
		return true;
	}

	public function verifyWopiToken(string $id, string $token): ?array
	{
		$token = WOPI::base64_decode_url_safe($token);

		$hash_id = $id;
		$hash = strtok($token, '_');
		$ttl = (int) strtok('_');
		$random = strtok('_');
		$readonly = (bool) strtok('_');
		$user_id = (int) strtok('');

		$hash_data = compact('hash_id', 'ttl', 'random', 'readonly', 'user_id');
		$check = WebDAV::hmac($hash_data, LOCAL_SECRET_KEY);

		if (!hash_equals($hash, $check)) {
			return null;
		}

		if ($ttl < time()) {
			return null;
		}

		$file = Files::getByHashId($hash_id);

		if (!$file) {
			return null;
		}

		$user = null;

		if ($user_id) {
			$user = Users::get($user_id);
		}

		return [
			WOPI::PROP_FILE_URI    => $file->uri(),
			WOPI::PROP_READ_ONLY   => (bool) $readonly,
			WOPI::PROP_USER_NAME   => $user ? $user->name() : 'Anonyme',
			WOPI::PROP_USER_ID     => $user ? $user->id() : null,
			WOPI::PROP_USER_AVATAR => $user ? $user->avatar_url() : null,
			WOPI::PROP_LAST_MODIFIED => $file->modified,
		];
	}
}
