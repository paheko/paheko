<?php

namespace Garradin\Files;

use Garradin\Config;
use Garradin\Users\Session;

use Garradin\Web\Router;

use Garradin\Entities\Files\File;

use const Garradin\{WWW_URL, ADMIN_URL};

class NextCloud_Compatibility
{
	static public function route($uri): void
	{
		if (0 === strpos($uri, 'remote.php/webdav/')) {
			$route = 'webdav';
		}
		else {
			$route = Router::NEXTCLOUD_ROUTES[$uri] ?? null;
		}

		if (!$route) {
			throw new \InvalidArgumentException('Invalid route');
		}

		header('Access-Control-Allow-Origin: *');
		header('Content-Type: application/json');

		$v = self::$route($uri);

		if (is_int($v)) {
			http_response_code($v);
			return;
		}

		echo json_encode($v, JSON_PRETTY_PRINT);
	}

	static public function status()
	{
		$values = [
			'installed'       => true,
			'maintenance'     => false,
			'needsDbUpgrade'  => false,
			'version'         => '2022.0.0.1',
			'versionstring'   => '2022.0.0',
			'edition'         => '',
			'productname'     => Config::getInstance()->org_name,
			'extendedSupport' => false,
		];

		return $values;
	}

	static public function login()
	{
		$method = $_SERVER['REDIRECT_REQUEST_METHOD'] ?? ($_SERVER['REQUEST_METHOD'] ?? null);

		if ($method != 'POST') {
			return 405;
		}

		$id = Session::getInstance()->generateAppToken();

		return [
			'poll' => [
				'token' => $id,
				'endpoint' => WWW_URL . 'index.php/login/v2/poll',
			],
			'login' => ADMIN_URL . 'login.php?tok=' . $id,
		];
	}

	static public function poll()
	{
		$method = $_SERVER['REDIRECT_REQUEST_METHOD'] ?? ($_SERVER['REQUEST_METHOD'] ?? null);

		if ($method != 'POST') {
			return 405;
		}

		if (empty($_POST['token']) || !ctype_alnum($_POST['token'])) {
			return 400;
		}

		$session = Session::getInstance()->verifyAppToken($_POST['token']);

		if (!$session) {
			return 404;
		}

		return [
			'server' => WWW_URL,
			'loginName' => $session->login,
			'appPassword' => $session->password,
		];
	}

	static public function webdav($uri)
	{
		$session = Session::getInstance();

		if (!$session->isLogged()) {
			if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				return 401;
			}

			if (!$session->checkAppCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				return 401;
			}
		}

		WebDAV::dispatchURI($uri, Router::NEXTCLOUD_DAV_ROUTE, File::CONTEXT_DOCUMENTS . '/');

		return null;
	}
}
