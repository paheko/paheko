<?php

namespace Paheko;

use KD2\HTTP;

class Security
{
	static public function checkWritePermissions(): array
	{
		$data_root = realpath(DATA_ROOT);
		$cache_root = realpath(CACHE_ROOT);
		$shared_cache_root = realpath(SHARED_CACHE_ROOT);
		$files_root = FILE_STORAGE_BACKEND === 'FileSystem' ? realpath(FILE_STORAGE_CONFIG) : null;
		$list = [];

		foreach (Utils::recursiveIterate(ROOT) as $path) {
			$real_path = realpath($path);

			// Data and cache paths can be written to
			if (0 === strpos($real_path, $data_root)
				|| 0 === strpos($real_path, $cache_root)
				|| 0 === strpos($real_path, $shared_cache_root)) {
				continue;
			}

			// File storage root is allowed
			if (null !== $files_root && 0 === strpos($real_path, $files_root)) {
				continue;
			}

			if (is_writable($real_path)) {
				$list[] = $path;
			}
		}

		return $list;
	}

	/**
	 * Try to fetch files using HTTP, that should not be reachable
	 * Of course if the local DNS is incorrectly configured, these requests will fail.
	 */
	static public function checkPrivateFilesAccess(): ?array
	{
		if (!WWW_URL) {
			return null;
		}

		$urls = [
			Utils::getLocalURL('/data/association.sqlite'),
			Utils::getLocalURL('/config.local.php'),
			Utils::getLocalURL('/include/init.php'),
		];

		$http = new HTTP;
		$out = [];

		foreach ($urls as $url) {
			$r = $http->GET($url);

			if (!$r->fail && $r->status && $r->status < 400) {
				$out[$url] = $r->status;
			}
		}

		return $out;
	}

	static public function scanFilesForSuspiciousCode(): array
	{
		if (FILE_STORAGE_CONFIG !== 'FileSystem') {
			return [];
		}

		$found = [];

		foreach (Utils::recursiveIterate(FILE_STORAGE_BACKEND) as $path) {
			if (preg_match('!<\?(?:php|=)|\?>!', file_get_contents($path))) {
				$found[] = $path;
			}
		}

		return $found;
	}

	static public function scanCacheForSuspiciousFunctionCalls(): array
	{
		$roots = [USER_TEMPLATES_CACHE_ROOT, STATIC_CACHE_ROOT, SHARED_USER_TEMPLATES_CACHE_ROOT, SMARTYER_CACHE_ROOT];
		$roots = array_unique($roots);
		$found = [];

		foreach ($roots as $root) {
			foreach (Utils::recursiveIterate($root) as $path) {
				if (is_dir($path)) {
					continue;
				}

				if (preg_match(';<\?(?:(?!\?>).)*?\b(exec|system|passthru|system|base64_decode|eval|dl|popen|rename|unlink|symlink|file|fopen|fsockopen|show_source|highlight_file|file_get_contents|file_put_contents|readfile|phpinfo|ini_set|move_uploaded_file|posix_[a-z_]+|pcntl_[a-z_]+|curl_[a-z_]+|proc_[a-z_]+|dl)\s*\(;s', file_get_contents($path), $match)) {
					$found[$path] = $match[1];
				}
			}
		}

		return $found;
	}


	static public function getReport(?string $version = null): \stdClass
	{
		return (object) [
			'open_basedir'        => !empty(OPEN_BASEDIR),
			//'dangerous_functions' => self::checkDangerousFunctions(),
			'cache_suspicious'    => self::scanCacheForSuspiciousFunctionCalls(),
			'files_suspicious'    => self::scanFilesForSuspiciousCode(),
			'write_permissions'   => self::checkWritePermissions(),
			'private_exposed'     => self::checkPrivateFilesAccess(),
			'manifest'            => self::verifyManifest($version),
		];
	}

	static public function verifyManifest(?string $version = null): \stdClass
	{
		$version ??= paheko_version();

		// The URL is hardcoded on purpose
		$url = 'https://fossil.kd2.org/paheko/raw/' . $version;

		$list = file($url);
		$mismatch = [];
		$present = [];

		foreach ($list as $line) {
			$line = trim($line);

			// Only include files from src
			if (substr($line, 0, 6) !== 'F src/') {
				continue;
			}

			$line = explode(' ', $line);
			$path = substr($line[1], 4);

			if (hash_file('sha3-256', $path) !== $line[2]) {
				$mismatch[] = $path;
			}

			$present[] = $path;
		}

		$extra = [];
		$root = realpath(ROOT);
		$exclude_roots = [USER_TEMPLATES_CACHE_ROOT,
			STATIC_CACHE_ROOT,
			SHARED_USER_TEMPLATES_CACHE_ROOT,
			SMARTYER_CACHE_ROOT,
			PLUGINS_ROOT,
			CONFIG_FILE,
			DESKTOP_CONFIG_FILE,
			ROOT . '/include/lib/KD2',
		];

		$exclude_roots = array_filter($exclude_roots);
		$exclude_roots = array_map('realpath', $exclude_roots);
		$exclude_roots = array_unique($exclude_roots);

		foreach (Utils::recursiveIterate($root) as $path) {
			$path = realpath($path);

			if (is_dir($path) || substr(strtolower($path), -4) !== '.php') {
				continue;
			}

			foreach ($exclude_roots as $e) {
				if (strpos($path, $e) === 0) {
					continue(2);
				}
			}

			$path = substr($path, strlen($root) + 1);

			if (!in_array($path, $present)) {
				$extra[] = $path;
			}
		}

		return (object) compact('extra', 'mismatch');
	}
}