<?php

namespace Garradin;

class Static_Cache
{
	const EXPIRE = 3600; // 1h
	const CLEAN_EXPIRE = 86400; // 1 day

	protected static function _getCacheDir()
	{
		return DATA_ROOT . '/cache/static';
	}

	protected static function _getCachePath($id)
	{
		$id = 'cache_' . sha1($id);
		return self::_getCacheDir() . '/' . $id;
	}

	static public function store($id, $content)
	{
		$path = self::_getCachePath($id);
		return (bool) file_put_contents($path, $content);
	}

	static public function expired($id, $expire = self::EXPIRE)
	{
		$path = self::_getCachePath($id);
		$time = @filemtime($path);

		if (!$time)
		{
			return true;
		}

		return ($time > (time() - (int)$expire)) ? false : true;
	}

	static public function get($id)
	{
		$path = self::_getCachePath($id);
		return file_get_contents($path);
	}

	static public function display($id)
	{
		$path = self::_getCachePath($id);
		return readfile($path);
	}

	static public function getPath($id)
	{
		return self::_getCachePath($id);
	}

	static public function remove($id)
	{
		$path = self::_getCachePath($id);
		return unlink($path);
	}

	static public function clean($expire = self::CLEAN_EXPIRE)
	{
		$dir = self::_getCacheDir();
		$d = dir($dir);

		$expire = time() - $expire;

		while ($file = $d->read())
		{
			if ($file[0] == '.')
			{
				continue;
			}

			if (filemtime($dir . '/' . $file) > $expire)
			{
				unlink($dir . '/' . $file);
			}
		}

		$d->close();

		return true;
	}
}
