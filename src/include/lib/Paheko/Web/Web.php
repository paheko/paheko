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
	const BREADCRUMBS_SQL = '
		WITH RECURSIVE parents(title, status, id_parent, uri, id, level) AS (
			SELECT title, status, id_parent, uri, id, 1 FROM web_pages WHERE id = %s
			UNION ALL
			SELECT p.title, p.status, p.id_parent, p.uri, p.id, level + 1
			FROM web_pages p
				JOIN parents ON parents.id_parent = p.id
		)
		SELECT id, title, uri, status FROM parents ORDER BY level DESC;';

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

	static public function listCategories(?int $id_parent): array
	{
		$sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY title COLLATE U_NOCASE;', self::getParentClause($id_parent), Page::TYPE_CATEGORY);
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
			'draft' => [
				'label' => 'Brouillon',
				'select' => sprintf('CASE WHEN p.status = %s THEN 1 ELSE 0 END',
					$db->quote(Page::STATUS_DRAFT),
				),
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
