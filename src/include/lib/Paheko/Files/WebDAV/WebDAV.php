<?php

namespace Paheko\Files\WebDAV;

use Paheko\Utils;
use Paheko\Web\Router;

use KD2\WebDAV\Server as KD2_WebDAV;
use KD2\WebDAV\Exception;

use const Paheko\{WOPI_DISCOVERY_URL, WWW_URL, ADMIN_URL, WEBDAV_LOG_FILE, WOPI_LOG_FILE};

class WebDAV extends KD2_WebDAV
{
	protected function html_directory(string $uri, iterable $list): ?string
	{
		$out = parent::html_directory($uri, $list);

		if (null !== $out) {
			$options = [
				'wopi_discovery_url' => WOPI_DISCOVERY_URL,
				'server_url' => WWW_URL,
				'webdav_url' => WWW_URL . 'dav/',
				'autosave' => true,
			];

			$uri = WWW_URL . 'dav/' . $uri;
			$js = ADMIN_URL . 'static/scripts/lib/webdav.min.js';

			$out = str_replace('</head>', sprintf('<script type="text/javascript" src="%s"></script>
				<script type="text/javascript">window.onload = () => browser.init(%s, %s);</script>', $js, json_encode($uri), json_encode($options)), $out);
			$out = str_replace('<body>', '<body><noscript>Please enable javascript</noscript><div style="opacity:0">', $out);
		}

		return $out;
	}

	public function log(string $message, ...$params)
	{
		$is_wopi = substr($message, 0, 5) === 'WOPI:';

		if ($is_wopi) {
			if (!WOPI_LOG_FILE) {
				return;
			}

			$message = substr($message, 6);
		}
		elseif (!$is_wopi) {
			if (!WEBDAV_LOG_FILE) {
				return;
			}
		}

		Router::log($is_wopi ? 'WOPI' : 'WEBDAV', $message, ...$params);
	}
}
