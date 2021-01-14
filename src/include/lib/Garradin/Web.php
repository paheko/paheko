<?php

namespace Garradin;

use Garradin\Entities\Web\Page;
use Garradin\Web\Template;

use KD2\DB\EntityManager as EM;

class Web
{
	static public function search(string $search, bool $online_only = true): array
	{
		if (strlen($search) > 100) {
			throw new UserException('Recherche trop longue : maximum 100 caractères');
		}

		$where = '';

		if ($online_only) {
			$where = sprintf('p.status = %d AND ', Page::STATUS_ONLINE);
		}

		$query = sprintf('SELECT
			p.*,
			snippet(files_search, \'<b>\', \'</b>\', \'...\', -1, -50) AS snippet,
			rank(matchinfo(files_search), 0, 1.0, 1.0) AS points
			FROM files_search AS s
			INNER JOIN web_pages AS p USING (id)
			WHERE %s files_search MATCH ?
			ORDER BY points DESC
			LIMIT 0,50;', $where);

		return DB::getInstance()->get($query, $search);
	}

	static public function listCategories(?int $parent): array
	{
		$where = $parent ? sprintf('parent_id = %d', $parent) : 'parent_id IS NULL';
		$sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY title COLLATE NOCASE;', $where, Page::TYPE_CATEGORY);
		return EM::getInstance(Page::class)->all($sql);
	}

	static public function listPages(?int $parent, bool $order_by_date = true): array
	{
		$where = $parent ? sprintf('parent_id = %d', $parent) : 'parent_id IS NULL';
		$order = $order_by_date ? 'modified DESC' : 'title COLLATE NOCASE';
		$sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY %s;', $where, Page::TYPE_PAGE, $order);
		return EM::getInstance(Page::class)->all($sql);
	}

	static public function listCategoriesTree(?int $current = null): array
	{
		$db = DB::getInstance();
		$flat = $db->get('SELECT id, parent_id, title FROM web_pages ORDER BY title COLLATE NOCASE;');

		$parents = [];

		foreach ($flat as $node) {
			if (!isset($parents[$node->parent_id])) {
				$parents[$node->parent_id] = [];
			}

			$parents[(int) $node->parent_id][] = $node;
		}

		$build_tree = function (int $parent_id, int $level) use ($build_tree, $parents): array {
			$nodes = [];

			if (!isset($parents[$parent_id])) {
				return $nodes;
			}

			foreach ($parents[$parent_id] as $node) {
				$node->level = $level;
				$node->children = $build_tree($node->id, $level + 1);
				$nodes[] = $node;
			}

			return $nodes;
		};

		return $build_tree(0, 0);
	}

	static public function getByURI(string $uri): ?Page
	{
		return EM::findOne(Page::class, 'SELECT * FROM @TABLE WHERE uri = ?;', $uri);
	}

	static public function get(int $id): ?Page
	{
		return EM::findOneById(Page::class, $id);
	}

	static public function dispatchURI()
	{
		$uri = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

		header('HTTP/1.1 200 OK', 200, true);

		if ($pos = strpos($uri, '?')) {
			$uri = substr($uri, 0, $pos);
		}
		else {
			// WWW_URI inclus toujours le slash final, mais on veut le conserver ici
			$uri = substr($uri, strlen(WWW_URI) - 1);
		}

		if ($uri == '/') {
			$skel = 'index.html';
		}
		// Redirect old URLs (pre-1.1)
		elseif ($uri == '/feed/atom/') {
			Utils::redirect('/atom.xml');
		}
		// URLs with an ending slash are categories
		elseif (substr($uri, -1) == '/') {
			$skel = 'category.html';
			$_GET['uri'] = $_REQUEST['uri'] = substr($uri, 1, -1);
		}
		elseif (preg_match('!^/admin/!', $uri)) {
			http_response_code(404);
			throw new UserException('Cette page n\'existe pas.');
		}
		else {
			$_GET['uri'] = $_REQUEST['uri'] = substr($uri, 1);

			// Custom templates
			if (preg_match('!^[\w\d_.-]+$!i', $_GET['uri'])) {
				$tpl = new Template($_GET['uri']);

				if ($tpl->exists()) {
					$tpl->serve();
					return;
				}
			}

			// Fallback to article.html
			$skel = 'article.html';
		}

		$tpl = new Template($skel);
		$tpl->serve();
	}
}