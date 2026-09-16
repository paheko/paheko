<?php

namespace Paheko\Files\WebDAV;

use KD2\WebDAV\Exception;
use KD2\WebDAV\WOPI;

use const Paheko\WOPI_DISCOVERY_URL;

class Server
{
	/**
	 * WOPI routes are only available to users logged-in in /admin, or using sharing links
	 * People logged-in with a webdav cookie won't be able to use WOPI
	 */
	static public function wopiRoute(?string $uri = null): bool
	{
		if (!WOPI_DISCOVERY_URL) {
			return false;
		}

		if (0 !== strpos($uri, '/wopi/')) {
			return false;
		}

		$wopi = new WOPI;
		$dav = new WebDAV;
		$storage = new Storage(null);
		$dav->setStorage($storage);
		$wopi->setServer($dav);

		return $wopi->route($uri);
	}

	static public function route(?string $uri = null): bool
	{
		$uri = '/' . ltrim($uri, '/');

		if (self::wopiRoute($uri)) {
			return true;
		}

		$dav = new WebDAV;
		$nc = new NextCloud;
		$storage = new Storage(Session::getInstance(), $nc);
		$dav->setStorage($storage);

		$method = $_SERVER['REQUEST_METHOD'] ?? null;

		// Always say YES to OPTIONS
		if ($method == 'OPTIONS') {
			$dav->http_options();
			return true;
		}

		$nc->setServer($dav);

		if ($nc->route($uri)) {
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

		try {
			// Stop here if auth didn't suceed
			if (!self::requireAuth()) {
				return true;
			}
		}
		catch (Exception $e) {
			$dav->error($e);
			return true;
		}

		$dav->setBaseURI('/dav/');

		return $dav->route($uri);
	}

	static public function requireAuth(): bool
	{
		$session = Session::getInstance();

		if ($session->isLogged()) {
			return true;
		}

		$login = $_SERVER['PHP_AUTH_USER'] ?? null;
		$password = $_SERVER['PHP_AUTH_PW'] ?? null;

		if (empty($login) || empty($password)) {
			// Not logged-in, require login
			http_response_code(401);
			header('WWW-Authenticate: Basic realm="Please login"');
			return false;
		}

		if ($session->loginAPI($login, $password)) {
			return true;
		}

		return $session->loginDAV($login, $password);
	}
}
