<?php

namespace Garradin\Files\WebDAV;

use KD2\WebDAV\WOPI;

use Garradin\Users\Session;

use const Garradin\WOPI_DISCOVERY_URL;

class Server
{
	static public function route(?string $uri = null): bool
	{
		$uri = '/' . ltrim($uri, '/');

		$dav = new WebDAV;
		$dav->setStorage(new Storage);

		header('Access-Control-Allow-Origin: *', true);
		$method = $_SERVER['REQUEST_METHOD'] ?? null;

		// Always say YES to OPTIONS
		if ($method == 'OPTIONS') {
			$dav->http_options();
			return true;
		}


		if (WOPI_DISCOVERY_URL) {
			$wopi = new WOPI;
			$wopi->setServer($dav);

			if ($wopi->route($uri)) {
				return true;
			}
		}

		$nc = new NextCloud($dav);

		if ($r = $nc->route($uri)) {
			// NextCloud route already replied something, stop here
			return true;
		}

		// If NextCloud layer didn't return anything
		// it means we fall back to the default WebDAV server
		// available on the root path. We need to handle a
		// classic login/password auth here.

		if (0 !== strpos($uri, '/dav/')) {
			return false;
		}

		if (!self::auth()) {
			http_response_code(401);
			header('WWW-Authenticate: Basic realm="Please login"');
			return true;
		}

		$dav->setBaseURI('/dav/');

		return $dav->route($uri);
	}

	static public function auth(): bool
	{
		$session = Session::getInstance();
		if ($session->isLogged()) {
			return true;
		}

		if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
			return false;
		}

		if ($session->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
			return true;
		}

		return false;
	}
}
