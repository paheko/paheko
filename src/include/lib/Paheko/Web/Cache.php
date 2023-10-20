<?php

namespace Paheko\Web;

use Paheko\Utils;
use Paheko\UserException;

use const Paheko\{DATA_ROOT, ROOT, WEB_CACHE_ROOT, WWW_URL};

/**
 * Create static cache as symlinks or static files for the website
 */
class Cache
{
	static protected ?string $root = null;

	static public function getRoot(): ?string
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

	static public function getPath(string $uri, bool $append_extension = true): string
	{
		$uri = rawurldecode($uri);
		$uri = '/' . ltrim($uri, '/');

		$target = self::$root . '/' . md5($uri);

		if ($append_extension) {
			$ext = self::getFileExtension($uri) ?? '.html';
			$target .= $ext;
		}

		return $target;
	}

	static public function getFileExtension(string $name): ?string
	{
		if (preg_match('/\.[a-z0-9]{1,10}$/', $name, $match)) {
			return $match[0];
		}

		return null;
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

		$target = self::getPath($uri, false);

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

		self::$root = rtrim(self::getRoot(), '/');

		if (!file_exists(self::$root)) {
			Utils::safe_mkdir(self::$root, 0777, true);
		}

		// Create symlink for self-hosting with .htaccess
		if (!file_exists(ROOT . '/www/.cache') && file_exists(DATA_ROOT . '/cache/web')) {
			if (!is_writable(ROOT . '/www')) {
				throw new UserException('Le répertoire "'. ROOT . '/www" n\'est pas accessible en écriture.');
			}

			@symlink(DATA_ROOT . '/cache/web', ROOT . '/www/.cache');
		}

		return true;
	}

	static public function link(string $uri, string $destination): void
	{
		if (!self::init()) {
			return;
		}

		$target = self::getPath($uri);

		@unlink($target);
		@symlink($destination, $target);
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

		$ext = self::getFileExtension($uri);
		$is_html = false !== stripos($html, '<html');
		$target = self::getPath($uri);

		// Do not store in cache if URI doesn't have an extension
		// and is not HTML, this is to avoid serving eg. XML files as HTML
		if (!$ext && !$is_html) {
			return;
		}

		if ($is_html) {
			$expire = time() + 3600;

			$close = sprintf('<script type="text/javascript">
				document.addEventListener(\'DOMContentLoaded\', () => {
					var now = +(new Date) / 1000;
					if (now < %d || location.hash) {
						return;
					}

					fetch(location.href + \'?__reload\').then(r => r.text()).then(r => {
						var x = window.pageX, y = window.pageY;
						document.open();
						document.write(r);
						document.close();
						window.scrollTo(x, y);
					});
				});
				</script>
				<!-- Cache generated on: %s --></body', $expire, date('Y-m-d H:i:s'));

			$html = str_ireplace('</body', $close, $html);
		}

		file_put_contents($target, $html);
	}
}
