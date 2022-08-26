<?php

namespace Garradin\Files;

use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Files\Files;

use KD2\WebDAV as KD2_WebDAV;
use KD2\WebDAV_Exception;

use const Garradin\WWW_URI;

class WebDAV extends KD2_WebDAV
{
	const LOCK = true;

	/**
	 * These file names will be ignored when doing a PUT
	 * as they are garbage, coming from some OS
	 */
	const PUT_IGNORE_PATTERN = '!^~(?:lock\.|^\._)|^(?:\.DS_Store|Thumbs\.db|desktop\.ini)$!';

	static public function dispatchURI(string $uri)
	{
		$w = new static;

		try {
			if (!$w->route(WWW_URI . 'dav/', $uri)) {
				throw new \RuntimeException('Invalid URL');
			}
		}
		catch (UserException $e) {
			if (http_response_code() < 400) {
				http_response_code(500);
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
	}

	protected function getLock(string $uri, ?string $token = null): ?string
	{
		// It is important to check also for a lock on parent directory as we support depth=1
		$sql = 'SELECT scope FROM locks WHERE (uri = ? OR uri = ?)';
		$params = [$uri, dirname($uri)];

		if ($token) {
			$sql .= ' AND token = ?';
			$params[] = $token;
		}

		$sql .= ' LIMIT 1';

		return DB::getInstance()->firstColumn($sql, ...$params);
	}

	protected function lock(string $uri, string $token, string $scope): void
	{
		DB::getInstance()->preparedQuery('REPLACE INTO locks VALUES (?, ?, ?, datetime(\'now\', \'+5 minutes\'));', $uri, $token, $scope);
	}

	protected function unlock(string $uri, string $token): void
	{
		DB::getInstance()->preparedQuery('DELETE FROM locks WHERE uri = ? AND token = ?;', $uri, $token);
	}

	protected function list(string $uri): iterable
	{
		foreach (Files::list($uri) as $file) {
			yield $file->name;
		}
	}

	protected function get(string $uri): ?array
	{
		// Unused
	}

	protected function http_get(string $uri): ?string
	{
		$file = Files::get($uri);

		if (!$file) {
			throw new WebDAV_Exception('File Not Found', 404);
		}

		if ($file->type == $file::TYPE_DIRECTORY) {
			header('Content-Type: text/html');

			echo '<ul>';

			foreach (Files::list($uri) as $file) {
				printf('<li><a href="%s">%s</a></li>', rawurlencode($file->name), $file->name);
			}

			exit;
		}

		$file->serve();
		exit;
	}

	protected function exists(string $uri): bool
	{
		return Files::exists($uri);
	}

	protected function metadata(string $uri, bool $all = false): ?array
	{
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

		$target = Files::get($uri);

		if ($target && $target->type === $target::TYPE_DIRECTORY) {
			throw new WebDAV_Exception('Target is a directory', 409);
		}

		$new = $target ? true : false;

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
		$target = Files::get($uri);

		if (!$target) {
			throw new WebDAV_Exception('Target does not exist', 404);
		}

		$target->delete();
	}

	protected function copymove(bool $move, string $uri, string $destination): bool
	{
		$source = Files::get($uri);

		if (!file_exists($source)) {
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
		if (Files::exists($uri)) {
			throw new WebDAV_Exception('There is already a file with that name', 405);
		}

		if (!Files::exists(Utils::dirname($uri))) {
			throw new WebDAV_Exception('The parent directory does not exist', 409);
		}

		Files::mkdir($uri);
	}
}
