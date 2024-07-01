<?php

namespace Paheko\Web;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Files\WebDAV\Server as WebDAV_Server;

use Paheko\Web\Web;

use Paheko\API;
use Paheko\Config;
use Paheko\Plugins;
use Paheko\Entities\Plugin;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\Users\Users;
use Paheko\UserTemplate\Modules;

use Paheko\Users\Session;

use \KD2\HTML\Markdown;

use const Paheko\{WWW_URI, ADMIN_URL, BASE_URL, WWW_URL, HELP_URL, ROOT, HTTP_LOG_FILE, WEBDAV_LOG_FILE, WOPI_LOG_FILE};

class Router
{
	const DAV_ROUTES = [
		'dav',
		'wopi',
		'remote.php',
		'index.php',
		'status.php',
		'ocs',
		'avatars',
	];

	static public function route(string $uri = null): void
	{
		$uri ??= !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

		$uri = parse_url($uri, \PHP_URL_PATH);

		// WWW_URI inclus toujours le slash final, mais on veut le conserver ici
		$uri = substr($uri, strlen(WWW_URI) - 1);

		// This might be changed later
		http_response_code(200);

		$uri = ltrim($uri, '/');

		$first = ($pos = strpos($uri, '/')) ? substr($uri, 0, $pos) : null;
		$method = $_SERVER['REQUEST_METHOD'] ?? $_SERVER['REDIRECT_REQUEST_METHOD'];

		if (HTTP_LOG_FILE) {
			$qs = $_SERVER['QUERY_STRING'] ?? null;
			$headers = apache_request_headers();

			self::log('ROUTER', "<= %s %s\nFrom: %s\nRequest headers:\n  %s",
				$method,
				$uri . ($qs ? '?' : '') . $qs,
				$_SERVER['REMOTE_ADDR'],
				implode("\n  ", array_map(fn ($v, $k) => $k . ': ' . $v, $headers, array_keys($headers)))
			);

			if ($method != 'GET' && $method != 'OPTIONS' && $method != 'HEAD') {
				//self::log('ROUTER', "<= Request body:\n%s", file_get_contents('php://input'));
			}
		}

		// Redirect old URLs (pre-1.1)
		if ($uri === 'feed/atom/') {
			Utils::redirect('/atom.xml');
		}
		elseif ($uri === 'favicon.ico') {
			http_response_code(301);
			header('Location: ' . Config::getInstance()->fileURL('favicon'), true);
			return;
		}
		// Default robots.txt if website is disabled
		elseif ($uri === 'robots.txt' && Config::getInstance()->site_disabled) {
			http_response_code(200);
			header('Content-Type: text/plain');
			echo "User-agent: *\nDisallow: /admin/\n";
			echo "User-agent: GPTBot\nDisallow: /\n";
			return;
		}
		// Private file sharing
		elseif ($first === 's') {
			$_GET['uri'] = substr($uri, 2);
			require ROOT . '/www/admin/share.php';
			return;
		}
		// Users avatars
		elseif ($first === 'user' && strpos($uri, 'user/avatar/') !== false) {
			Users::serveAvatar(substr($uri, strlen('user/avatar/')));
			return;
		}
		// Add trailing slash to URLs if required
		elseif (($first === 'p' || $first === 'm') && preg_match('!^(?:admin/p|p|m)/\w+$!', $uri)) {
			Utils::redirect('/' . $uri . '/');
		}
		elseif ((($first === 'admin' && 0 === strpos($uri, 'admin/p/')) || $first === 'p')
			&& preg_match('!^(?:admin/p|p)/(' . Plugins::NAME_REGEXP . ')/(.*)$!', $uri, $match)
			&& Plugins::exists($match[1])) {
			$uri = ($first === 'admin' ? 'admin/' : 'public/') . $match[2];

			$name = Utils::basename($uri);

			// Do not expose templates if the name begins with an underscore
			// this is not really a security issue, but they will probably fail
			if (substr($name, 0, 1) === '_' || $name === Plugin::META_FILE) {
				throw new UserException('This address is private', 403);
			}

			if ($match[2] === 'icon.svg' || substr($uri, -3) === '.md') {
				$r = Plugins::routeStatic($match[1], $uri);

				if ($r) {
					return;
				}
			}
			else {
				$plugin = Plugins::get($match[1]);

				if ($plugin && $plugin->enabled) {
					$plugin->route($uri);
					return;
				}
			}
		}

		// Other admin/plugin routes are not found
		if ($first === 'admin' || $first === 'p') {
			throw new UserException('Cette page ne semble pas exister.', 404);
		}
		elseif ($first === 'api') {
			API::routeHttpRequest(substr($uri, 4));
			return;
		}
		// Route WebDAV requests to WebDAV server
		elseif ((in_array($uri, self::DAV_ROUTES) || in_array($first, self::DAV_ROUTES))
			&& WebDAV_Server::route($uri)) {
			return;
		}
		// Redirect PROPFIND requests to WebDAV, required for some WebDAV clients
		elseif ($method === 'PROPFIND') {
			header('Location: /dav/documents/');
			return;
		}
		// Don't try to route paths with no slash, files are always in a sub-directory
		elseif ($uri && false !== strpos($uri, '/') && self::routeFile($uri)) {
			return;
		}

		// Redirect to ADMIN_URL if website is disabled
		// (but not for content.css)
		if (Config::getInstance()->site_disabled && $uri !== 'content.css' && $first !== 'm') {
			if ($uri === '') {
				Utils::redirect(ADMIN_URL);
			}
			else {
				throw new UserException('Cette page n\'existe pas.', 404);
			}
		}

		// Let modules handle the request
		Modules::route($uri);
	}

	static public function routeFile(string $uri): bool
	{
		// Redirect old sharing links (pre 1.3.7), FIXME: remove this after 1.5.0
		if (isset($_GET['s'])) {
			$_GET['path'] = $uri;
			$_GET['hash'] = $_GET['s'];
			require ROOT . '/www/admin/share_legacy.php';
			return true;
		}

		$context = strtok($uri, '/');
		strtok('');

		$size = null;

		if (false !== strpos($uri, 'px.') && preg_match('/\.([\da-z-]+px)\.(?:webp|svg)$/', $uri, $match)) {
			$uri = substr($uri, 0, -strlen($match[0]));
			$size = $match[1];
		}

		$file = Files::getFromURI($uri);

		// We can't serve directories
		if ($file && $file->isDir()) {
			$file = null;
		}

		if (!$file) {
			// URL has a context but is not a file? stop here
			if ($context && array_key_exists($context, File::CONTEXTS_NAMES)) {
				throw new UserException('Cette adresse n\'existe pas ou plus.', 404);
			}

			return false;
		}

		if ($file->trash) {
			throw new UserException('Cette adresse a été supprimée.', 410);
		}

		foreach ($_GET as $key => $v) {
			if (array_key_exists($key, File::ALLOWED_THUMB_SIZES)) {
				$size = $key;
				break;
			}
		}

		$session = Session::getInstance();

		$signal = Plugins::fire('http.request.file.before', true, compact('file', 'uri', 'session'));

		if ($signal && $signal->isStopped()) {
			// If a plugin handled the request, let's stop here
			return true;
		}

		$file->validateCanRead($session);

		if ($size) {
			$file->serveThumbnail($size);
		}
		else {
			$file->serve(isset($_GET['download']));
		}

		Plugins::fire('http.request.file.after', false, compact('file', 'uri', 'session'));

		return true;
	}

	static public function log(string $type, string $message, ...$params)
	{
		$file = null;

		if ($type === 'ROUTER') {
			$file = HTTP_LOG_FILE;
		}
		elseif ($type === 'WEBDAV') {
			$file = WEBDAV_LOG_FILE;
		}
		elseif ($type === 'WOPI') {
			$file = WOPI_LOG_FILE;
		}

		if (!$file) {
			return;
		}

		static $logs = null;

		if (!$logs) {
			$logs = [];
			register_shutdown_function(function () use (&$logs) {
				foreach ($logs as $file => $content) {
					if (!$content) {
						continue;
					}

					file_put_contents($file, $content . "\n", FILE_APPEND);
				}
			});
		}

		$logs[$file] ??= date('[d/m/Y H:i:s]') . "\n";
		$logs[$file] .= vsprintf($message, $params) . "\n";
	}
}
