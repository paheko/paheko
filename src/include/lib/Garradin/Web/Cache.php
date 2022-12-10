<?php

namespace Garradin\Web;

use Garradin\Utils;

use const Garradin\CACHE_ROOT;

class Cache
{
	const ROOT = CACHE_ROOT . '/web';

	static public function clear(): void
	{
		Utils::deleteRecursive(self::ROOT, false);
	}

	static public function init(): bool
	{
		// Symlinks on Windows… Not sure if that works
		if (PHP_OS_FAMILY == 'Windows') {
			return false;
		}

		if (!file_exists(ROOT . '/www/_cache')) {
			symlink(self::ROOT, ROOT . '/www/_cache');
		}

		return true;
	}

	static public function link(string $uri, string $destination): void
	{
		if (!self::init()) {
			return;
		}

		$target = self::ROOT . '/' . ltrim($uri, '/');
		$dir = Utils::dirname($target);

		if (!file_exists($dir)) {
			Utils::safe_mkdir($dir);
		}

		@unlink($target);
		symlink($destination, $target);
	}

	static public function store(string $uri, string $html): void
	{
		if (!self::init()) {
			return;
		}

		$target = self::ROOT . '/' . ltrim($uri, '/');
		$dir = Utils::dirname($target);

		if (!file_exists($dir)) {
			Utils::safe_mkdir($dir);
		}

		$expire = time() + 1800;

		$close = sprintf('<script type="text/javascript">
			document.addEventListener(\'DOMContentLoaded\', () => {
				var now = +(new Date) / 1000;
				if (now < %d) {
					return;
				}

				fetch(location.href + \'?reload\').then(r => r.text()).then(r => {
					document.open();
					document.write(r);
					document.close();
				});
			});
			</script></body', $expire);

		$html = str_ireplace('</body', $close, $html);
		file_put_contents($target, $html);
	}
}
