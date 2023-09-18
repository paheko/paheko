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
	static public function search(string $search): array
	{
		$results = Files::search($search, File::CONTEXT_WEB . '%');

		foreach ($results as &$result) {
			$result->path = substr($result->path, strlen(File::CONTEXT_WEB) + 1);
			$result->breadcrumbs = [];
			$path = '';

			foreach (explode('/', $result->path) as $part) {
				$path = trim($path . '/' . $part, '/');
				$result->breadcrumbs[$path] = $part;
			}
		}

		return $results;
	}

	static protected function getParentClause(?string $parent): string
	{
		if ($parent) {
			return 'parent = ' . DB::getInstance()->quote($parent);
		}
		else {
			return 'parent IS NULL';
		}
	}

	static public function listCategories(?string $parent): array
	{
		$sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY title COLLATE U_NOCASE;', self::getParentClause($parent), Page::TYPE_CATEGORY);
		return EM::getInstance(Page::class)->all($sql);
	}

	static public function listPages(?string $parent, bool $order_by_date = true): array
	{
		$order = $order_by_date ? 'published DESC' : 'title COLLATE U_NOCASE';
		$sql = sprintf('SELECT * FROM @TABLE WHERE %s AND type = %d ORDER BY %s;', self::getParentClause($parent), Page::TYPE_PAGE, $order);
		return EM::getInstance(Page::class)->all($sql);
	}

	static public function listAll(): array
	{
		$sql = 'SELECT * FROM @TABLE ORDER BY title COLLATE U_NOCASE;';
		return EM::getInstance(Page::class)->all($sql);
	}

	static public function getDraftsList(?string $parent): DynamicList
	{
		$list = self::getPagesList($parent);
		$list->setParameter('status', Page::STATUS_DRAFT);
		$list->setPageSize(1000);
		return $list;
	}

	static public function getPagesList(?string $parent): DynamicList
	{
		$columns = [
			'id' => [],
			'path' => [
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
		$conditions = self::getParentClause($parent) . ' AND type = :type AND status = :status';

		$list = new DynamicList($columns, $tables, $conditions);
		$list->setParameter('type', Page::TYPE_PAGE);
		$list->setParameter('status', Page::STATUS_ONLINE);
		$list->orderBy('title', false);
		return $list;
	}

	static public function get(string $path): ?Page
	{
		return EM::findOne(Page::class, 'SELECT * FROM @TABLE WHERE path = ?;', $path);
	}

	static public function getById(int $id): ?Page
	{
		return EM::findOne(Page::class, 'SELECT * FROM @TABLE WHERE id = ?;', $id);
	}

	static public function getByURI(string $uri): ?Page
	{
		return EM::findOne(Page::class, 'SELECT * FROM @TABLE WHERE uri = ?;', $uri);
	}

	static public function getAttachmentFromURI(string $uri): ?File
	{
		if (strpos($uri, '/') === false) {
			return null;
		}

		$path = DB::getInstance()->firstColumn('SELECT path FROM web_pages WHERE uri = ?;', Utils::dirname($uri));

		if (!$path) {
			return null;
		}

		return Files::getFromURI(File::CONTEXT_WEB . '/' . $path . '/' . Utils::basename($uri));
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
