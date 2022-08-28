<?php

namespace Garradin\Files;

use Garradin\Config;
use Garradin\Utils;
use Garradin\Users\Session;

use Garradin\Web\Router;

use Garradin\Entities\Files\File;

use const Garradin\{WWW_URL, ADMIN_URL};

class NextCloud_Compatibility
{
	const AUTH_REDIRECT_URL = 'nc://login/server:%s&user:%s&password:%s';

	static public function route($uri): void
	{
		$route = array_filter(Router::NEXTCLOUD_ROUTES, fn($k) => 0 === strpos($uri, $k), ARRAY_FILTER_USE_KEY);

		if (!$route) {
			throw new \InvalidArgumentException('Invalid route');
		}

		$route = current($route);

		header('Access-Control-Allow-Origin: *');

		$v = self::$route($uri);

		if (is_int($v)) {
			http_response_code($v);
			return;
		}
		else {
			if ($route == 'shares') {
				header('Content-Type: text/xml; charset=utf-8');
				echo '<?xml version="1.0"?>';
				echo self::toXML($v);
			}
			else {
				header('Content-Type: application/json');
				echo json_encode($v, JSON_PRETTY_PRINT);
			}
		}
	}

	static protected function toXML(array $array): string
	{
		$out = '';

		foreach ($array as $key => $v) {
			$out .= '<' . $key .'>';

			if (is_array($v)) {
				$out .= self::toXML($v);
			}
			else {
				$out .= htmlspecialchars((string) $v, ENT_XML1);
			}

			$out .= '</' . $key .'>';

		}

		return $out;
	}

	static public function webdav($uri)
	{
		$session = Session::getInstance();

		if (!$session->isLogged()) {
			if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				header('WWW-Authenticate: Basic realm="Please login"');
				return 401;
			}

			if (!$session->checkAppCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])
				&& !$session->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				header('WWW-Authenticate: Basic realm="Please login"');
				return 401;
			}
		}

		foreach (Router::NEXTCLOUD_ROUTES as $route => $method) {
			if ($method != 'webdav') {
				continue;
			}

			if (0 === strpos($uri, $route)) {
				$base_uri = rtrim($route, '/') . '/';
				break;
			}
		}

		// Android app is using "/remote.php/dav/files/null//" as root
		// so let's alias that as well
		if (preg_match('!^' . preg_quote($base_uri, '!') . 'files/[a-z]+/+!', $uri, $match)) {
			$base_uri = $match[0];
		}

		WebDAV::dispatchURI($uri, $base_uri, File::CONTEXT_DOCUMENTS . '/');

		return null;
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

	static public function login_v2()
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

	static public function capabilities()
	{
		return self::ocs([
			'version' => [
				'major' => 2022,
				'minor' => 0,
				'micro' => 0,
				'string' => '2022.0.0',
				'edition' => '',
				'extendedSupport' => false,
			],
			'capabilities' => [
				'core' => ['webdav-root' => array_search('webdav', Router::NEXTCLOUD_ROUTES), 'pollinterval' => 60],
			],
		]);
	}

	static public function login_v1()
	{
		Utils::redirect('!login.php?tok=flow');
	}

	static public function user()
	{
		return self::ocs([
			'id' => 'null',
			'enabled' => true,
			'email' => null,
			'storageLocation' => '/tmp/whoknows',
			'role' => '',
			'quota' => [
				'quota' => -3, // fixed value
				'relative' => 0, // fixed value
				'free' => 20000,
				'total' => 2000000,
				'used' => 200,
			],
		]);
	}

	static public function shares()
	{
		return self::ocs([]);
	}

	static protected function empty()
	{
		return self::ocs([]);
	}

	static protected function ocs(array $data = []): array
	{
		return ['ocs' => [
			'meta' => ['status' => 'ok', 'statuscode' => 200, 'message' => 'OK'],
			'data' => $data,
		]];
	}
}
