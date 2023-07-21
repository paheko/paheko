<?php

namespace Paheko\Files\WebDAV;

use Paheko\Utils;
use Paheko\Web\Router;

use KD2\WebDAV\Server as KD2_WebDAV;
use KD2\WebDAV\Exception;

use const Paheko\{WOPI_DISCOVERY_URL, WWW_URL, ADMIN_URL};

class WebDAV extends KD2_WebDAV
{
/*
	protected function html_directory(string $uri, iterable $list): ?string
	{
		Utils::redirect('!docs/?path=' . rawurlencode($uri));
		return null;
	}
*/

	protected function html_directory(string $uri, iterable $list): ?string
	{
		$out = parent::html_directory($uri, $list);

		if (null !== $out) {
			if (WOPI_DISCOVERY_URL) {
				$out = str_replace('<html', sprintf('<html data-wopi-discovery-url="%s" data-wopi-host-url="%s"', WOPI_DISCOVERY_URL, WWW_URL . 'wopi/'), $out);
			}

			$body = sprintf('<body style="opacity: 0">
				<script type="text/javascript" src="%1$sstatic/scripts/lib/webdav.fr.js"></script>
				<script type="text/javascript" src="%1$sstatic/scripts/lib/webdav.js"></script>',
				ADMIN_URL);
			$out = str_replace('<body>', $body, $out);
		}

		return $out;
	}

	public function log(string $message, ...$params)
	{
		Router::log('DAV: ' . $message, ...$params);
	}
}
