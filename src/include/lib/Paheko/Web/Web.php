<?php

namespace Paheko\Web;

use Paheko\Entities\Web\Page;
use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Utils;

use KD2\DB\EntityManager as EM;

class Web
{
	// Always have a LIMIT for recursive queries, or we might stay in a recursive loop
	const BREADCRUMBS_SQL = '
		WITH RECURSIVE parents(title, status, inherited_status, id_parent, uri, id, level) AS (
			SELECT title, status, inherited_status, id_parent, uri, id, 1 FROM web_pages WHERE id = %s
			UNION ALL
			SELECT p.title, p.status, p.inherited_status, p.id_parent, p.uri, p.id, level + 1
			FROM web_pages p
				JOIN parents ON parents.id_parent = p.id
			LIMIT 100
		)
		SELECT id, title, uri, status, inherited_status FROM parents ORDER BY level DESC;';

	/**
	 * Updates inherited_status column by using a recusive SQL function
	 * SQLite does not write to database if there is nothing to change,
	 * so this shouldn't take too much resource.
	 */
	static public function updateChildrenInheritedStatus(): void
	{
		$sql = 'WITH RECURSIVE children(status, inherited_status, id_parent, id, level, new_status) AS (
				SELECT status, inherited_status, id_parent, id, 1, status FROM web_pages WHERE id_parent IS NULL
				UNION ALL
				SELECT p.status, p.inherited_status, p.id_parent, p.id, level + 1, CASE WHEN p.status < children.new_status THEN p.status ELSE children.new_status END
				FROM web_pages p
					JOIN children ON children.id = p.id_parent
				LIMIT 100000
			)
			UPDATE web_pages SET inherited_status = IFNULL((SELECT new_status FROM children WHERE id = web_pages.id), status);';

		DB::getInstance()->exec($sql);
	}

	static public function listAllChildren(?int $id, string $order = 'title'): \Generator
	{
		$sql = '
			WITH RECURSIVE children(published, modified, title, status, inherited_status, id_parent, uri, id, level) AS (
				%s
				UNION ALL
				SELECT p.published, p.modified, p.title, p.status, p.inherited_status, p.id_parent, p.uri, p.id, level + 1
				FROM web_pages p
					JOIN children ON children.id = p.id_parent
				LIMIT 100000
			)
			SELECT id, title, published, modified, id_parent, level, uri, status, inherited_status FROM children ORDER BY %s;';

		if ($id) {
			$union = sprintf('SELECT published, modified, title, status, inherited_status, id_parent, uri, id, 1 FROM web_pages WHERE id = %d', $id);
		}
		else {
			$union = 'SELECT published, modified, title, status, inherited_status, id_parent, uri, id, 1 FROM web_pages WHERE id_parent IS NULL';
		}

		if ($order === 'title') {
			$order = 'title COLLATE U_NOCASE ASC';
		}
		elseif ($order === 'published' || $order === 'modified' || $order === 'id') {
			$order .= ' ASC';
		}
		else {
			throw new \InvalidArgumentException('Unknown order: ' . $order);
		}

		$sql = sprintf($sql, $union, $order);
		return DB::getInstance()->iterate($sql);
	}

	static public function search(string $search): array
	{
		$results = Files::search($search, File::CONTEXT_WEB . '%');

		foreach ($results as &$result) {
			$path = substr($result->path, strlen(File::CONTEXT_WEB) + 1);
			$result->uri = strtok($path, '/');
			strtok('');
		}

		unset($result);

		return $results;
	}

	static public function getBreadcrumbs(int $id): array
	{
		return DB::getInstance()->getGrouped(sprintf(self::BREADCRUMBS_SQL, $id));
	}

	static protected function getParentClause(?int $id_parent): string
	{
		if ($id_parent) {
			return 'id_parent = ' . $id_parent;
		}
		else {
			return 'id_parent IS NULL';
		}
	}

	static public function listCategories(?int $id_parent, ?int $except = null): array
	{
		$except_clause = $except ? ' AND id != ' . (int)$except : '';
		$sql = sprintf('SELECT * FROM @TABLE
			WHERE %s AND type = %d %s
			ORDER BY title COLLATE U_NOCASE;',
			self::getParentClause($id_parent),
			Page::TYPE_CATEGORY,
			$except_clause
		);
		return EM::getInstance(Page::class)->all($sql);
	}

	static public function listPages(?int $id_parent, bool $order_by_date = true): array
	{
		$order = $order_by_date ? 'published DESC' : 'title COLLATE U_NOCASE';
		$sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY %s;', self::getParentClause($id_parent), Page::TYPE_PAGE, $order);
		return EM::getInstance(Page::class)->all($sql);
	}

	static public function listAll(): array
	{
		$sql = 'SELECT * FROM @TABLE ORDER BY title COLLATE U_NOCASE;';
		return EM::getInstance(Page::class)->all($sql);
	}

	static public function getDraftsList(?int $id_parent): DynamicList
	{
		$list = self::getPagesList($id_parent);
		$conditions = self::getParentClause($id_parent) . ' AND type = :type AND status = :status';
		$list->setConditions($conditions);
		$list->setPageSize(1000);
		return $list;
	}

	static public function getPagesList(?int $id_parent): DynamicList
	{
		$columns = [
			'id' => [],
			'uri' => [
			],
			'title' => [
				'label' => 'Titre',
				'order' => 'title COLLATE U_NOCASE %s',
			],
			'published' => [
				'label' => 'Publication',
			],
			'modified' => [
				'label' => 'Modification',
			],
		];

		$tables = Page::TABLE;
		$conditions = self::getParentClause($id_parent) . ' AND type = :type AND status != :status';

		$list = new DynamicList($columns, $tables, $conditions);
		$list->setParameter('type', Page::TYPE_PAGE);
		$list->setParameter('status', Page::STATUS_DRAFT);
		$list->orderBy('title', false);
		return $list;
	}

	static public function getAllList(): DynamicList
	{
		$db = DB::getInstance();

		$columns = [
			'id' => [],
			'uri' => [
			],
			'title' => [
				'label' => 'Titre',
				'order' => 'title COLLATE U_NOCASE %s',
			],
			'path' => [
				'label' => 'Catégorie',
				'select' => sprintf('(SELECT GROUP_CONCAT(title, \' > \') FROM (%s))',
					rtrim(sprintf(Web::BREADCRUMBS_SQL, 'p.id_parent'), '; ')),
			],
			'status' => [
				'label' => 'Statut',
				'order' => 'status %s, title COLLATE U_NOCASE %1$s',
			],
			'published' => [
				'label' => 'Publication',
			],
			'modified' => [
				'label' => 'Modification',
			],
		];

		$tables = Page::TABLE . ' AS p';

		$list = new DynamicList($columns, $tables);
		$list->orderBy('title', false);
		$list->setPageSize(null);
		return $list;
	}

	static public function getSitemap(): array
	{
		$list = self::listAllChildren(null, 'title');
		$all = [];

		foreach ($list as $item) {
			$item->children = [];
			$all[$item->id] = $item;
		}

		// Populate children arrays
		foreach ($all as $item) {
			if (!$item->id_parent) {
				continue;
			}

			$all[$item->id_parent]->children[] = $item;
		}

		// Remove children from top level
		foreach ($all as $item) {
			if ($item->id_parent) {
				unset($all[$item->id]);
			}
		}

		return $all;
	}

	static public function getByURI(string $uri): ?Page
	{
		return EM::findOne(Page::class, 'SELECT * FROM @TABLE WHERE uri = ?;', $uri);
	}

	static public function get(int $id): ?Page
	{
		return EM::findOne(Page::class, 'SELECT * FROM @TABLE WHERE id = ?;', $id);
	}

	static public function checkAllInternalPagesLinks(): array
	{
		$sql = 'SELECT * FROM @TABLE ORDER BY title COLLATE U_NOCASE;';
		$list = [];

		foreach (EM::getInstance(Page::class)->iterate($sql) as $page) {
			$list[$page->uri] = $page;
		}

		$errors = [];

		foreach ($list as $page) {
			if (count($page->checkInternalPagesLinks($list)) > 0) {
				$errors[] = $page;
			}
		}

		return $errors;
	}
}
