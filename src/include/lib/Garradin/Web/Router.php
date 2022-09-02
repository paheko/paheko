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
	// Order is important
	const NEXTCLOUD_ROUTES = [
		'status.php' => 'status',
		// Login v1, for Android app
		'index.php/login/flow' => 'login_v1',
		// Login v2, for desktop app
		'index.php/login/v2/poll' => 'poll',
		'index.php/login/v2' => 'login_v2',
		'ocs/v1.php/cloud/capabilities' => 'capabilities',
		'ocs/v2.php/cloud/capabilities' => 'capabilities',
		'ocs/v2.php/cloud/user' => 'user',
		'ocs/v1.php/cloud/user' => 'user',
		'ocs/v2.php/apps/files_sharing/api/v1/shares' => 'shares',
		'ocs/v2.php/apps/user_status/api/v1/predefined_statuses' => 'empty',
		'ocs/v2.php/core/navigation/apps' => 'empty',
		'remote.php/webdav/' => 'webdav',
		'remote.php/dav' => 'webdav',
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
		elseif (substr($uri, 0, 5) === 'form/') {
			$uri = substr($uri, 5);
			UserForms::serve($uri);
			return;
		}
		elseif (0 === strpos($uri, 'dav/')) {
			WebDAV::dispatchURI($uri);
			return;
		}
		elseif (array_filter(self::NEXTCLOUD_ROUTES, fn($k) => 0 === strpos($uri, $k), ARRAY_FILTER_USE_KEY)) {
			NextCloud_Compatibility::route($uri);
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
