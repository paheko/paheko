<?php

namespace Garradin\Web;

use Garradin\Utils;
use Garradin\UserException;

use const Garradin\{DATA_ROOT, ROOT, WEB_CACHE_ROOT, WWW_URL};

class Cache
{
	static protected ?string $root = null;

	static public function getPath(): ?string
	{
		$host = parse_url(WWW_URL, \PHP_URL_HOST);

		if (!$host) {
			return null;
		}

		$host = md5($host);

		$path = WEB_CACHE_ROOT;
		$path = strtr($path, [
			'%host%' => $host,
			'%host.2%' => substr($host, 0, 2),
		]);

		return $path;
	}

	static public function clear(): void
	{
		if (!self::init()) {
			return;
		}

		Utils::deleteRecursive(self::$root, false);
	}

	static public function delete(string $uri): void
	{
		if (!self::init()) {
			return;
		}

		$uri = rawurldecode($uri);
		$uri = '/' . ltrim($uri, '/');

		$target = self::$root . '/' . md5($uri);

		foreach (glob($target . '*') as $file) {
			Utils::safe_unlink($file);
		}
	}

	static public function init(): bool
	{
		if (!WEB_CACHE_ROOT) {
			return false;
		}

		// Symlinks on Windows… Not sure if that works
		if (PHP_OS_FAMILY == 'Windows') {
			return false;
		}

		// Only Apache is supported, no need to create useless cache files with other servers
		if (false === strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'Apache')) {
			return false;
		}

		if (isset(self::$root)) {
			return true;
		}

		self::$root = rtrim(self::getPath(), '/');

		if (!file_exists(self::$root)) {
			Utils::safe_mkdir(self::$root, 0777, true);
		}

		// Create symlink for self-hosting with .htaccess
		if (!file_exists(ROOT . '/www/.cache') && file_exists(DATA_ROOT . '/cache/web')) {
			if (!is_writable(ROOT . '/www')) {
				throw new UserException('Le répertoire "'. ROOT . '/www" n\'est pas accessible en écriture.');
			}
			symlink(DATA_ROOT . '/cache/web', ROOT . '/www/.cache');
		}

		return true;
	}

	static public function link(string $uri, string $destination, ?string $suffix = null): void
	{
		if (!self::init()) {
			return;
		}

		$uri = rawurldecode($uri);
		$uri = '/' . ltrim($uri, '/');

		$target = self::$root . '/' . md5($uri);

		if ($suffix) {
			$target .= '_' . $suffix;
		}

		@unlink($target);
		symlink($destination, $target);
	}

	static public function store(string $uri, string $html): void
	{
		// Do not store if the page content might be influenced by either POST, query string, or logged-in user
		if (!isset($_GET['__reload']) && ($_SERVER['REQUEST_METHOD'] != 'GET' || !empty($_SERVER['QUERY_STRING']) || isset($_COOKIE['pko']))) {
			return;
		}

		if (!self::init()) {
			return;
		}

		$uri = rawurldecode($uri);
		$uri = '/' . ltrim($uri, '/');

		$target = self::$root . '/' . md5($uri);

		if (false !== stripos($html, '<html')) {
			$expire = time() + 3600;

			$close = sprintf('<script type="text/javascript">
				document.addEventListener(\'DOMContentLoaded\', () => {
					var now = +(new Date) / 1000;
					if (now < %d) {
						return;
					}

					console.log(\'reloading\', now, %1$d);

					fetch(location.href + \'?__reload\').then(r => r.text()).then(r => {
						document.open();
						document.write(r);
						document.close();
					});
				});
				</script></body', $expire);

			$html = str_ireplace('</body', $close, $html);
		}

		file_put_contents($target, $html);
	}
}
