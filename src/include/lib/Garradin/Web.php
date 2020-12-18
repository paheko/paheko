<?php

namespace Garradin;

use Garradin\Entities\Web\Page;

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
}