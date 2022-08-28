<?php

namespace Garradin\Web;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Files\NextCloud_Compatibility;
use Garradin\Files\WebDAV;

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
	const NEXTCLOUD_ROUTES = [
		'status.php' => 'status',
		'index.php/login/v2' => 'login',
		'index.php/login/v2/poll' => 'poll',
	];

	const NEXTCLOUD_DAV_ROUTE = 'remote.php/webdav/';

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

		if (substr($uri, 0, 6) === 'admin/') {
			http_response_code(404);
			throw new UserException('Cette page n\'existe pas.');
		}
		// Redirect old URLs (pre-1.1)
		elseif ($uri == 'feed/atom/') {
			Utils::redirect('/atom.xml');
		}
		elseif ($uri == 'favicon.ico') {
			header('Location: ' . Config::getInstance()->fileURL('favicon'), true);
			return;
		}
		elseif (0 === strpos($uri, 'api/')) {
			API::dispatchURI(substr($uri, 4));
			return;
		}
		elseif (0 === strpos($uri, 'dav/')) {
			WebDAV::dispatchURI($uri);
			return;
		}
		elseif (0 === strpos($uri, self::NEXTCLOUD_DAV_ROUTE)
			|| array_key_exists($uri, self::NEXTCLOUD_ROUTES)) {
			NextCloud_Compatibility::route($uri);
			return;
		}
		elseif (substr($uri, 0, 5) === 'form/') {
			$uri = substr($uri, 5);
			UserForms::serve($uri);
			return;
		}
		elseif (($file = Files::getFromURI($uri))
			|| ($file = Web::getAttachmentFromURI($uri))) {
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
				$file->serve($session, isset($_GET['download']) ? true : false);
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
