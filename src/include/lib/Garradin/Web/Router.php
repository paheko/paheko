<?php

namespace Garradin\Web;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Files\WebDAV\Server as WebDAV_Server;

use Garradin\UserTemplate\UserForms;
use Garradin\Web\Skeleton;
use Garradin\Web\Web;

use Garradin\API;
use Garradin\Config;
use Garradin\Plugin;
use Garradin\UserException;
use Garradin\Utils;

use Garradin\Users\Session;

use const Garradin\{WWW_URI, ADMIN_URL, ROOT};

class Router
{
	const DAV_ROUTES = [
		'dav',
		'wopi',
		'remote.php',
		'index.php',
		'status.php',
		'ocs',
	];

	static public function route(): void
	{
		$uri = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

		if ($pos = strpos($uri, '?')) {
			$uri = substr($uri, 0, $pos);
		}

		// WWW_URI inclus toujours le slash final, mais on veut le conserver ici
		$uri = substr($uri, strlen(WWW_URI) - 1);

		// This might be changed later
		http_response_code(200);

		$uri = substr($uri, 1);

		$first = ($pos = strpos($uri, '/')) ? substr($uri, 0, $pos) : null;

		// Redirect old URLs (pre-1.1)
		if ($uri == 'feed/atom/') {
			Utils::redirect('/atom.xml');
		}
		elseif ($uri == 'favicon.ico') {
			header('Location: ' . Config::getInstance()->fileURL('favicon'), true);
			return;
		}
		elseif (preg_match('!^(admin/p|p)/(' . Plugin::PLUGIN_ID_REGEXP . ')/(.*)$!', $uri, $match)) {
			$plugin = new Plugin($match[2]);
			$public = $match[1] == 'p';
			$plugin->route($public, $match[3]);
			return;
		}
		elseif ('admin' == $first || 'p' == $first) {
			http_response_code(404);
			throw new UserException('Cette page n\'existe pas.');
		}
		elseif ('api' == $first) {
			API::dispatchURI(substr($uri, 4));
			return;
		}
		elseif ('form' == $first) {
			$uri = substr($uri, 5);
			UserForms::serve($uri);
			return;
		}
		elseif ((in_array($uri, self::DAV_ROUTES) || in_array($first, self::DAV_ROUTES))
			&& WebDAV_Server::route($uri)) {
			return;
		}
		elseif (Files::getContext($uri)
			&& (($file = Files::getFromURI($uri))
				|| ($file = Web::getAttachmentFromURI($uri)))) {
			$size = null;

			if ($file->image) {
				foreach ($_GET as $key => $v) {
					if (array_key_exists($key, File::ALLOWED_THUMB_SIZES)) {
						$size = $key;
						break;
					}
				}
			}

			$session = Session::getInstance();

			if (Plugin::fireSignal('http.request.file.before', compact('file', 'uri', 'session'))) {
				// If a plugin handled the request, let's stop here
				return;
			}

			if ($size) {
				$file->serveThumbnail($session, $size);
			}
			else {
				$file->serve($session, isset($_GET['download']), $_GET['s'] ?? null, $_POST['p'] ?? null);
			}

			Plugin::fireSignal('http.request.file.after', compact('file', 'uri', 'session'));

			return;
		}

		if (Config::getInstance()->site_disabled) {
			Utils::redirect(ADMIN_URL);
		}

		$page = null;

		if ($uri == '') {
			$skel = 'index.html';
		}
		elseif (($page = Web::getByURI($uri)) && $page->status == Page::STATUS_ONLINE) {
			$skel = $page->template();
			$page = $page->asTemplateArray();
		}
		// No page with this URI, then we expect this might be a skeleton path
		elseif (Skeleton::isValidPath($uri)) {
			$skel = $uri;
		}
		else {
			$skel = '404.html';
		}

		$s = new Skeleton($skel);
		$s->serve(compact('uri', 'page', 'skel'));
	}
}
