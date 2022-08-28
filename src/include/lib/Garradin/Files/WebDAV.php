<?php

namespace Garradin\Files;

use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Users\Session;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;

use KD2\WebDAV as KD2_WebDAV;
use KD2\WebDAV_Exception;

use const Garradin\WWW_URI;

class WebDAV extends KD2_WebDAV
{
	const LOCK = true;

	protected $original_uri = null;
	protected $root = null;

	/**
	 * These file names will be ignored when doing a PUT
	 * as they are garbage, coming from some OS
	 */
	const PUT_IGNORE_PATTERN = '!^~(?:lock\.|^\._)|^(?:\.DS_Store|Thumbs\.db|desktop\.ini)$!';

	const BANNED_USER_AGENTS = '!^WebDAVFS!';

	static public function dispatchURI(string $uri, string $base_uri = 'dav/', string $root = '')
	{
		if (isset($_SERVER['HTTP_USER_AGENT'])
			&& preg_match(self::BANNED_USER_AGENTS, $_SERVER['HTTP_USER_AGENT'])) {
			http_response_code(403);
			echo "Your WebDAV client is buggy, you need to use another client.";
			exit;
		}

		try {
			$session = Session::getInstance();

			if (!$session->isLogged()) {
				if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
					throw new UserException('Aucun identifiant n\'a été fourni', 401);
				}

				if (!$session->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
					throw new UserException('L\'identifiant n\'existe pas, le membre n\'a pas le droit de se connecter, ou le mot de passe est erroné.', 401);
				}
			}

			$w = new static;

			$uri = ltrim($uri, '/');
			$base_uri = ltrim($base_uri, '/');
			$w->original_uri = $uri;
			$w->root = $root;

			if (!$w->route('/' . $base_uri, '/' . $uri)) {
				throw new \RuntimeException('Invalid URL');
			}
		}
		catch (UserException $e) {
			$code = $e->getCode() < 400 ? 500 : $e->getCode();
			http_response_code($code);

			if ($code == 401) {
				header('WWW-Authenticate: Basic realm="Please login"');
			}

			echo $e->getMessage();
		}

		exit;
	}

	protected function log(string $message, ...$params)
	{
		if (PHP_SAPI == 'cli-server') {
			error_log(vsprintf($message, $params));
		}

		file_put_contents(\Garradin\ROOT . '/webdav.log', vsprintf($message, $params) . "\n", FILE_APPEND);
	}

	protected function getLock(string $uri, ?string $token = null): ?string
	{
		$uri = trim($this->root . $uri, '/');

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

	protected function lock(string $uri, string $token, string $scope): void
	{
		$uri = trim($this->root . $uri, '/');
		DB::getInstance()->preparedQuery('REPLACE INTO files_webdav_locks VALUES (?, ?, ?, datetime(\'now\', \'+5 minutes\'));', $uri, $token, $scope);
	}

	protected function unlock(string $uri, string $token): void
	{
		$uri = trim($this->root . $uri, '/');
		DB::getInstance()->preparedQuery('DELETE FROM files_webdav_locks WHERE uri = ? AND token = ?;', $uri, $token);
	}

	protected function list(string $uri): iterable
	{
		$uri = trim($this->root . $uri, '/');
		foreach (Files::list($uri) as $file) {
			yield $file->name => [
				'modified'   => $file->modified->getTimestamp(),
				'size'       => $file->size,
				'type'       => $file->mime,
				'collection' => $file->type == $file::TYPE_DIRECTORY,
			];
		}
	}

	protected function get(string $uri): ?array
	{
		// Unused
	}

	protected function http_get(string $uri): ?string
	{
		$list = null;
		$session = Session::getInstance();
		$access = Files::listReadAccessContexts($session);

		$uri = trim($this->root . $uri, '/');

		// Context roots don't exist in the files, but they are valid
		if (array_key_exists($uri, File::CONTEXTS_NAMES)) {
			if (!array_key_exists($uri, $access)) {
				throw new WebDAV_Exception('Ce répertoire n\'existe pas, ou vous n\'y avez pas accès', 404);
			}

			$type = File::TYPE_DIRECTORY;
		}
		elseif (!$uri) {
			$type = File::TYPE_DIRECTORY;
			$list = [];

			foreach ($access as $context => $name) {
				$list[] = (object) ['name' => $context, 'title' => $name, 'type' => File::TYPE_DIRECTORY];
			}
		}
		else {
			$context = strtok($uri, '/');

			if (!array_key_exists($context, $access)) {
				throw new WebDAV_Exception('Vous n\'avez pas accès à ce chemin', 403);
			}

			$file = Files::get($uri);

			if (!$file) {
				throw new WebDAV_Exception('File Not Found', 404);
			}

			if (!$file->checkReadAccess($session)) {
				throw new WebDAV_Exception('Vous n\'avez pas accès à ce chemin', 403);
			}

			$type = $file->type;
		}

		// Serve files
		if ($type != File::TYPE_DIRECTORY) {
			$file->serveAuto($session, $_GET);
			return null;
		}

		// Not a file: let's serve a directory listing if you are browsing with a web browser
		if (substr($this->original_uri, -1) != '/') {
			http_response_code(301);
			header(sprintf('Location: %s%s/', $this->base_uri, $uri), true);
			return null;
		}

		header('Content-Type: text/html');

		echo '<style>
			body { font-size: 1.1em; font-family: Arial, Helvetica, sans-serif; }
			table { border-collapse: collapse; }
			th, td { padding: .5em; text-align: left; border: 2px solid #ccc; }
			span { font-size: 40px; line-height: 40px; }
			img { max-width: 100px; max-height: 100px; }
			b { font-size: 1.4em; }
			td:nth-child(1) { text-align: center; }
			</style>
			<table>';

		printf('<title>%s</title><h2>%1$s</h2>', htmlspecialchars(str_replace('/', ' / ', $uri ?: 'Fichiers')));

		if ($uri != trim($this->root, '/')) {
			echo '<tr><td><span>&#x21B2;</span></td><th colspan=4><a href="../"><b>Retour</b></a></th></tr>';
		}

		$list ??= Files::list($uri);

		foreach ($list as $file) {
			if ($file->type == File::TYPE_DIRECTORY) {
				printf('<tr><td><span>&#x1F4C1;</span></td><th colspan=4><a href="%s/"><b>%s</b></a></th></tr>', rawurlencode($file->name), htmlspecialchars($file->title ?? $file->name));
			}
			else {
				if ($file->image) {
					$icon = sprintf('<a href="%s"><img src="%1$s?150px" /></a>', rawurlencode($file->name));
				}
				else {
					$icon = '<span>&#x1F5CE;</span>';
				}

				printf('<tr><td>%s</td><th><a href="%s">%s</a></th><td>%s</td><td>%s</td><td>%s</td></tr>',
					$icon,
					rawurlencode($file->name),
					htmlspecialchars($file->name),
					$file->mime,
					Utils::format_bytes($file->size),
					sprintf('<a href="%s?download">Télécharger</a>', rawurlencode($file->name))
				);
			}
		}

		return null;
	}

	protected function exists(string $uri): bool
	{
		$uri = trim($this->root . $uri, '/');
		return Files::exists($uri);
	}

	protected function metadata(string $uri, bool $all = false): ?array
	{
		$uri = trim($this->root . $uri, '/');

		if (!$uri || array_key_exists($uri, File::CONTEXTS_NAMES)) {
			return [
				'collection' => true,
			];
		}

		$file = Files::get($uri);

		if (!$file) {
			return null;
		}

		$meta = [
			'modified'   => $file->modified->getTimestamp(),
			'size'       => $file->size,
			'type'       => $file->mime,
			'collection' => $file->type == $file::TYPE_DIRECTORY,
		];

		if ($all) {
			$meta['created']  = null;
			$meta['accessed'] = null;
			$meta['hidden']   = false;
		}

		return $meta;
	}

	protected function put(string $uri, $pointer): bool
	{
		if (preg_match(self::PUT_IGNORE_PATTERN, basename($uri))) {
			return false;
		}

		$uri = trim($this->root . $uri, '/');

		$target = Files::get($uri);

		if ($target && $target->type === $target::TYPE_DIRECTORY) {
			throw new WebDAV_Exception('Target is a directory', 409);
		}

		$new = !$target ? true : false;

		if ($new) {
			Files::createFromPointer($uri, $pointer);
		}
		else {
			$target->store(compact('pointer'));
		}

		return $new;
	}

	protected function delete(string $uri): void
	{
		$uri = $this->root . $uri;
		$target = Files::get($uri);

		if (!$target) {
			throw new WebDAV_Exception('Target does not exist', 404);
		}

		$target->delete();
	}

	protected function copymove(bool $move, string $uri, string $destination): bool
	{
		$uri = trim($this->root . $uri, '/');
		$source = Files::get($uri);

		if (!$source) {
			throw new WebDAV_Exception('File not found', 404);
		}

		$overwritten = Files::exists($destination);

		if ($overwritten) {
			$this->delete($destination);
		}

		$method = $move ? 'rename' : 'copy';

		$source->$method($destination);

		return $overwritten;
	}

	protected function copy(string $uri, string $destination): bool
	{
		return $this->copymove(false, $uri, $destination);
	}

	protected function move(string $uri, string $destination): bool
	{
		return $this->copymove(true, $uri, $destination);
	}

	protected function mkcol(string $uri): void
	{
		$uri = trim($this->root . $uri, '/');

		if (Files::exists($uri)) {
			throw new WebDAV_Exception('There is already a file with that name', 405);
		}

		Files::mkdir($uri);
	}
}
