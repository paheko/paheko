<?php

namespace Garradin\Web;

use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use Garradin\Files\Files;
use Garradin\DB;
use Garradin\DynamicList;
use Garradin\Utils;
use Garradin\ValidationException;

use KD2\DB\EntityManager as EM;

use const Garradin\FILE_STORAGE_BACKEND;

class Web
{
	static public function search(string $search): array
	{
		$results = Files::search($search, File::CONTEXT_WEB . '%');

		foreach ($results as &$result) {
			$result->path = Utils::dirname(substr($result->path, strlen(File::CONTEXT_WEB) + 1));
			$result->breadcrumbs = [];
			$path = '';

			foreach (explode('/', $result->path) as $part) {
				$path = trim($path . '/' . $part, '/');
				$result->breadcrumbs[$path] = $part;
			}
		}

		return $results;
	}

	static public function listCategories(string $parent): array
	{
		$sql = 'SELECT * FROM @TABLE WHERE parent = ? AND type = ? ORDER BY title COLLATE U_NOCASE;';
		return EM::getInstance(Page::class)->all($sql, $parent, Page::TYPE_CATEGORY);
	}

	static public function listPages(string $parent, bool $order_by_date = true): array
	{
		$order = $order_by_date ? 'published DESC' : 'title COLLATE U_NOCASE';
		$sql = sprintf('SELECT * FROM @TABLE WHERE parent = ? AND type = %d ORDER BY %s;', Page::TYPE_PAGE, $order);
		return EM::getInstance(Page::class)->all($sql, $parent);
	}

	static public function listAll(string $parent): array
	{
		$sql = 'SELECT * FROM @TABLE WHERE parent = ? ORDER BY title COLLATE U_NOCASE;';
		return EM::getInstance(Page::class)->all($sql, $parent);
	}

	static public function getDraftsList(string $parent): DynamicList
	{
		$list = self::getPagesList($parent);
		$list->setParameter('status', Page::STATUS_DRAFT);
		$list->setPageSize(1000);
		return $list;
	}

	static public function getPagesList(string $parent): DynamicList
	{
		$columns = [
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
		$conditions = 'parent = :parent AND type = :type AND status = :status';

		$list = new DynamicList($columns, $tables, $conditions);
		$list->setParameter('parent', $parent);
		$list->setParameter('type', Page::TYPE_PAGE);
		$list->setParameter('status', Page::STATUS_ONLINE);
		$list->orderBy('title', false);
		return $list;
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

		$path = DB::getInstance()->firstColumn('SELECT path FROM web_pages WHERE uri = ?;', Utils::dirname($uri));

		if (!$path) {
			return null;
		}

		return Files::getFromURI(File::CONTEXT_WEB . '/' . $path . '/' . Utils::basename($uri));
	}

	static public function checkAllInternalLinks(): array
	{
		$sql = 'SELECT * FROM @TABLE ORDER BY title COLLATE U_NOCASE;';
		$list = [];

		foreach (EM::getInstance(Page::class)->iterate($sql) as $page) {
			if (!$page->file()) {
				continue;
			}

			$list[$page->uri] = $page;
		}

		$errors = [];

		foreach ($list as $page) {
			if (count($page->checkInternalLinks($list))) {
				$errors[] = $page;
			}
		}

		return $errors;
	}
}
