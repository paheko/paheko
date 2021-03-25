<?php

namespace Garradin\Web;

use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use Garradin\Web\Skeleton;
use Garradin\Files\Files;
use Garradin\API;
use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Membres\Session;

use KD2\DB\EntityManager as EM;

use const Garradin\{WWW_URI, ADMIN_URL};

class Web
{
	static public function search(string $search): array
	{
		$results = Files::search($search, File::CONTEXT_WEB . '%');

		foreach ($results as &$result) {
			$result->uri = dirname(substr($result->path, strlen(File::CONTEXT_WEB) + 1));
			$result->breadcrumbs = [];
			$path = '';

			foreach (explode('/', $result->uri) as $part) {
				$path = trim($path . '/' . $part, '/');
				$result->breadcrumbs[$path] = $part;
			}
		}

		return $results;
	}

	static public function sync(?string $parent)
	{
		$path = trim(File::CONTEXT_WEB . '/' . $parent, '/');

		$exists = [];

		foreach (Files::callStorage('list', $path) as $file) {
			if ($file->type != File::TYPE_DIRECTORY) {
				continue;
			}

			$exists[] = $file->path;
		}

		$db = DB::getInstance();

		$in_db = $db->getAssoc('SELECT path, 1 FROM web_pages WHERE parent = ?;', $parent);
		$in_db = array_keys($in_db);

		$deleted = array_diff($in_db, $exists);
		$new = array_diff($exists, $in_db);

		if ($deleted) {
			$db->exec(sprintf('DELETE FROM web_pages WHERE %s;', $db->where('path', $deleted)));
		}

		foreach ($new as $file) {
			$f = Files::get($file . '/index.txt');

			if (!$f) {
				continue;
			}

			Page::fromFile($f)->save();
		}
	}

	static public function listCategories(string $parent): array
	{
		$sql = 'SELECT * FROM @TABLE WHERE parent = ? AND type = ? ORDER BY title COLLATE NOCASE;';
		return EM::getInstance(Page::class)->all($sql, $parent, Page::TYPE_CATEGORY);
	}

	static public function listPages(string $parent, bool $order_by_date = true): array
	{
		$order = $order_by_date ? 'published DESC' : 'title COLLATE NOCASE';
		$sql = sprintf('SELECT * FROM @TABLE WHERE parent = ? AND type = %d ORDER BY %s;', Page::TYPE_PAGE, $order);
		return EM::getInstance(Page::class)->all($sql, $parent);
	}

	static public function get(string $path): ?Page
	{
		$page = EM::findOne(Page::class, 'SELECT * FROM @TABLE WHERE path = ?;', $path);

		if ($page && !$page->file()) {
			return null;
		}

		return $page;
	}

	static public function getByURI(string $uri): ?Page
	{
		$page = EM::findOne(Page::class, 'SELECT * FROM @TABLE WHERE uri = ?;', $uri);

		if ($page && !$page->file()) {
			return null;
		}

		return $page;
	}

	static public function getAttachmentFromURI(string $uri): ?File
	{
		if (strpos($uri, '/') === false) {
			return null;
		}

		$path = DB::getInstance()->firstColumn('SELECT path FROM web_pages WHERE uri = ?;', dirname($uri));

		if (!$path) {
			return null;
		}

		return Files::getFromURI(File::CONTEXT_WEB . '/' . $path . '/' . basename($uri));
	}

	static public function dispatchURI()
	{
		$uri = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

		if ($pos = strpos($uri, '?')) {
			$uri = substr($uri, 0, $pos);
		}

		// WWW_URI inclus toujours le slash final, mais on veut le conserver ici
		$uri = substr($uri, strlen(WWW_URI) - 1);

		http_response_code(200);

		$uri = substr($uri, 1);

		// Redirect old URLs (pre-1.1)
		if ($uri == 'feed/atom/') {
			Utils::redirect('/atom.xml');
		}
		elseif (substr($uri, 0, 4) == 'api/') {
			API::dispatchURI(substr($uri, 4));
			exit;
		}
		elseif (substr($uri, 0, 6) === 'admin/') {
			http_response_code(404);
			throw new UserException('Cette page n\'existe pas.');
		}
		elseif (($file = Files::getFromURI($uri))
			|| ($file = self::getAttachmentFromURI($uri))) {
			$size = null;

			foreach ($_GET as $key => $value) {
				if (substr($key, -2) == 'px') {
					$size = (int)substr($key, 0, -2);
					break;
				}
			}

			$session = Session::getInstance();

			if ($size) {
				$file->serveThumbnail($session, $size);
			}
			else {
				$file->serve($session, isset($_GET['download']) ? true : false);
			}

			return;
		}

		if (Config::getInstance()->get('site_disabled')) {
			Utils::redirect(ADMIN_URL);
		}

		$page = null;

		if ($uri == '') {
			$skel = 'index.html';
		}
		elseif ($page = self::getByURI($uri)) {
			$skel = $page->template();
			$page = $page->asTemplateArray();
		}
		else {
			// Trying to see if a custom template with this name exists
			if (preg_match('!^[\w\d_.-]+$!i', $uri)) {
				$s = new Skeleton($uri);

				if ($s->exists()) {
					$s->serve();
					return;
				}
			}
			elseif ($file = Files::getFromURI(File::CONTEXT_SKELETON . '/' . $uri)) {
				$file->serve();
				return;
			}

			$skel = '404.html';
		}

		$s = new Skeleton($skel);
		$s->serve(compact('uri', 'page', 'skel'));
	}
}
