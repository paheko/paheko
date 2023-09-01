<?php

namespace Paheko;

class Static_Cache
{
	const EXPIRE = 3600; // 1h
	const CLEAN_EXPIRE = 86400; // 1 day

	static protected function ensureParentDirectoryExists(string $path): void
	{
		$parent = Utils::dirname($path);

		if (!file_exists($parent)) {
			Utils::safe_mkdir($parent, fileperms(STATIC_CACHE_ROOT), true);
		}
	}

	static public function getPath(string $id): string
	{
		$id = sha1(DB_FILE . $id);
		$path = STATIC_CACHE_ROOT . '/' . substr($id, 0, 2) . '/' . $id;

		self::ensureParentDirectoryExists($path);

		return $path;
	}

	static public function store(string $id, string $content): bool
	{
		$path = self::getPath($id);
		return (bool) file_put_contents($path, $content);
	}

	static public function storeFromPointer(string $id, $pointer): bool
	{
		$path = self::getPath($id);

		$fp = fopen($path, 'wb');
		$ok = stream_copy_to_stream($pointer, $fp);
		fclose($fp);

		return (bool) $ok;
	}

	static public function expired(string $id, int $expire = self::EXPIRE): bool
	{
		$path = self::getPath($id);
		$time = @filemtime($path);

		if (!$time)
		{
			return true;
		}

		return ($time > (time() - (int)$expire)) ? false : true;
	}

	static public function get(string $id): string
	{
		$path = self::getPath($id);
		return file_get_contents($path);
	}

	static public function display(string $id): void
	{
		$path = self::getPath($id);
		readfile($path);
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
