<?php

namespace Garradin\Web;

use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use Garradin\Web\Skeleton;
use Garradin\Files\Files;
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
				$path = trim($part . '/', '/');
				$result->breadcrumbs[$path] = $part;
			}
		}

		return $results;
	}

	static public function listCategories(?string $parent): array
	{
		$params = [];

		if (null === $parent) {
			$where = 'parent IS NULL';
		}
		else {
			$where = 'parent = ?';
			$params[] = $parent;
		}

		$sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY title COLLATE NOCASE;', $where, Page::TYPE_CATEGORY);
		return EM::getInstance(Page::class)->all($sql, ...$params);
	}

	static public function listPages(?string $parent, bool $order_by_date = true): array
	{
		$params = [];

		if (null === $parent) {
			$where = 'parent IS NULL';
		}
		else {
			$where = 'parent = ?';
			$params[] = $parent;
		}

		$order = $order_by_date ? 'published DESC' : 'title COLLATE NOCASE';
		$sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY %s;', $where, Page::TYPE_PAGE, $order);
		return EM::getInstance(Page::class)->all($sql, ...$params);
	}

	static public function getByURI(string $uri): ?Page
	{
		return EM::findOne(Page::class, 'SELECT * FROM @TABLE WHERE path = ?;', $uri);
	}

	static public function get(int $id): ?Page
	{
		return EM::findOneById(Page::class, $id);
	}

	static public function dispatchURI()
	{
		$uri = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

		if ($pos = strpos($uri, '?')) {
			$uri = substr($uri, 0, $pos);
		}
		else {
			// WWW_URI inclus toujours le slash final, mais on veut le conserver ici
			$uri = substr($uri, strlen(WWW_URI) - 1);
		}

		http_response_code(200);

		$uri = substr($uri, 1);

		// Redirect old URLs (pre-1.1)
		if ($uri == 'feed/atom/') {
			Utils::redirect('/atom.xml');
		}
		elseif (substr($uri, 0, 6) === 'admin/') {
			http_response_code(404);
			throw new UserException('Cette page n\'existe pas.');
		}
		elseif ($file = Files::getFromURI($uri)) {
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

		if (Config::getInstance()->get('desactiver_site')) {
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

			$skel = '404.html';
		}

		$s = new Skeleton($skel);
		$s->serve(compact('uri', 'page', 'skel'));
	}
}