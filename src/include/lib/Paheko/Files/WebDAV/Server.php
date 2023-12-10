<?php

namespace Paheko\Files\WebDAV;

use Paheko\Users\Session as UserSession;

use KD2\WebDAV\WOPI;

use const Paheko\WOPI_DISCOVERY_URL;

class Server
{
	/**
	 * WOPI routes are only available to users logged-in in /admin/
	 * Not people logged-in using webdav
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
		$storage = new Storage(UserSession::getInstance());
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
		$nc = new NextCloud($dav);
		$storage = new Storage(Session::getInstance(), $nc);
		$dav->setStorage($storage);

		$method = $_SERVER['REQUEST_METHOD'] ?? null;

		// Always say YES to OPTIONS
		if ($method == 'OPTIONS') {
			$dav->http_options();
			return true;
		}


		$nc->setServer($dav);

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

		$login = $_SERVER['PHP_AUTH_USER'] ?? null;
		$password = $_SERVER['PHP_AUTH_PW'] ?? null;

		if (!isset($login, $password)) {
			return false;
		}

		if ($session->loginAPI($login, $password)) {
			return true;
		}

		if ($session->login($login, $password)) {
			return true;
		}

		return false;
	}
}
