<?php

namespace Paheko;

use DateTime;

class Static_Cache
{
	const EXPIRE = 3600; // 1h
	const CLEAN_EXPIRE = 86400; // 1 day

	static protected function ensureParentDirectoryExists(string $path): void
	{
		$parent = Utils::dirname($path);

		if (!file_exists($parent)) {
			if (file_exists(STATIC_CACHE_ROOT)) {
				$perms = fileperms(STATIC_CACHE_ROOT);
			}
			else {
				$perms = 0777;
			}

			Utils::safe_mkdir($parent, $perms, true);
		}
	}

	static public function getPath(string $id): string
	{
		$id = sha1(DB_FILE . $id);
		$path = STATIC_CACHE_ROOT . '/' . substr($id, 0, 2) . '/' . $id;

		self::ensureParentDirectoryExists($path);

		return $path;
	}

	static public function create(string $id, ?\DateTime $expiry = null): string
	{
		$path = self::getPath($id);
		self::setExpiry($id, $expiry);
		return $path;
	}

	static public function setExpiry(string $id, ?DateTime $expiry): bool
	{
		$path = self::getPath($id);
		return touch($path, $expiry ? $expiry->getTimestamp() : 0);
	}

	static public function store(string $id, string $content, ?DateTime $expiry = null): bool
	{
		$path = self::getPath($id);
		return (bool) file_put_contents($path, $content)
			&& self::setExpiry($id, $expiry);
	}

	static public function storeFromPointer(string $id, $pointer, ?DateTime $expiry = null): bool
	{
		$path = self::getPath($id);

		$fp = fopen($path, 'wb');
		$ok = stream_copy_to_stream($pointer, $fp);
		fclose($fp);

		return (bool) $ok && self::setExpiry($id, $expiry);
	}

	static public function export(string $id, $data, ?DateTime $expiry = null): bool
	{
		return self::store($id, json_encode($data), $expiry);
	}

	static public function import(string $id)
	{
		$data = self::get($id);

		if (null === $data) {
			return null;
		}

		$data = json_decode($data, true);
		return $data;
	}

	static public function hasExpired(string $id): bool
	{
		$path = self::getPath($id);

		if (!file_exists($path)) {
			return true;
		}

		$time = @filemtime($path);

		if ($time === false) {
			return true;
		}
		// Zero = never expire
		elseif (!$time) {
			return false;
		}

		if ($time < time()) {
			Utils::safe_unlink($path);
			return true;
		}

		return false;
	}

	static public function prune(): void
	{
		$now = time();

		foreach (glob(STATIC_CACHE_ROOT . '/*') as $path) {
			$dir = opendir($path);

			while($file = $dir->open()) {
				if (substr($file, 0, 1) === '.' || is_dir($file)) {
					continue;
				}

				if (@filemtime($file) < $now) {
					Utils::safe_unlink($file);
				}
			}

			$dir->close();
		}
	}

	static public function get(string $id): ?string
	{
		if (self::hasExpired($id)) {
			return null;
		}

		$path = self::getPath($id);
		return file_get_contents($path);
	}

	static public function display(string $id): bool
	{
		if (self::hasExpired($id)) {
			return false;
		}

		$path = self::getPath($id);
		readfile($path);
		return true;
	}

	static public function exists(string $id): bool
	{
		return file_exists(self::getPath($id));
	}

	static public function remove(string $id): bool
	{
		$path = self::getPath($id);
		return Utils::safe_unlink($path);
	}
}
