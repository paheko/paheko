<?php

namespace Paheko\Web;

use Paheko\DB;
use Paheko\Utils;
use Paheko\ValidationException;

use Paheko\Entities\Web\Page;
use Paheko\Entities\Files\File;
use Paheko\Files\Files;

use const Paheko\FILE_STORAGE_BACKEND;

/**
 * This is used to sync from index.txt files
 * @todo FIXME TODO remove in Paheko 1.4
 * @deprecated Only used to migrate from versions < 1.3
 */
class Sync
{
	static protected function importFromRaw(Page $page, string $str): bool
	{
		$str = preg_replace("/\r\n?/", "\n", $str);
		$str = explode("\n\n----\n\n", $str, 2);

		if (count($str) !== 2) {
			return false;
		}

		list($meta, $content) = $str;

		$meta = explode("\n", trim($meta));

		foreach ($meta as $line) {
			$key = strtolower(trim(strtok($line, ':')));
			$value = trim(strtok(''));

			if ($key == 'title') {
				$page->set('title', $value);
			}
			elseif ($key == 'published') {
				$page->set('published', new \DateTime($value));
			}
			elseif ($key == 'modified') {
				$page->set('modified', new \DateTime($value));
			}
			elseif ($key == 'format') {
				$value = strtolower($value);

				if (!array_key_exists($value, Page::FORMATS_LIST)) {
					throw new \LogicException('Unknown format: ' . $value);
				}

				$page->set('format', $value);
			}
			elseif ($key == 'type') {
				$value = strtolower($value);

				if ($value == strtolower(Page::TYPES[Page::TYPE_CATEGORY])) {
					$value = Page::TYPE_CATEGORY;
				}
				elseif ($value == strtolower(Page::TYPES[Page::TYPE_PAGE])) {
					$value = Page::TYPE_PAGE;
				}
				else {
					throw new \LogicException('Unknown type: ' . $value);
				}

				$page->set('type', $value);
			}
			elseif ($key == 'status') {
				$value = strtolower($value);

				if (!array_key_exists($value, Page::STATUS_LIST)) {
					throw new \LogicException('Unknown status: ' . $value);
				}

				$page->set('status', $value);
			}
			else {
				// Ignore other metadata
			}
		}

		$page->set('content', trim($content, "\n\r"));

		return true;
	}

	static protected function loadFromFile(Page $page, File $file): void
	{
		// Don't update if page was modified in DB since
		if ($page->modified && $page->modified > $file->modified) {
			return;
		}

		if (!self::importFromRaw($page, $file->fetch())) {
			throw new \LogicException('Invalid page content: ' . $file->parent);
		}

		if (empty($page->modified)) {
			$page->set('modified', $file->modified);
		}

		if (!isset($page->type) || $page->type != $page::TYPE_CATEGORY) {
			$type = $page::TYPE_PAGE;

			foreach (Files::list($page->dir_path) as $file) {
				if ($file->isDir()) {
					$type = $page::TYPE_CATEGORY;
					break;
				}
			}

			$page->set('type', $type);
		}
		else {
			$page->set('type', $page::TYPE_CATEGORY);
		}
	}

	static public function fromFile(File $file): Page
	{
		$page = new Page;

		$path = substr($file->parent, strlen(File::CONTEXT_WEB . '/'));

		$page->importForm([
			'path' => $path,
			'parent' => Utils::dirname($path) ?: null,
			'uri' => Utils::basename($file->parent),
		]);

		self::loadFromFile($page, $file);
		return $page;
	}

	/**
	 * This syncs the whole website between the actual files and the web_pages table
	 */
	static public function sync(bool $force = false): array
	{
		// This is only useful if web pages are stored outside of the database
		if (FILE_STORAGE_BACKEND == 'SQLite' && !$force) {
			return [];
		}

		$path = File::CONTEXT_WEB;
		$errors = [];

		$list = iterator_to_array(Files::listRecursive($path, null, false));
		$list = array_filter($list, fn($a) => !$a->isDir() && $a->name == 'index.txt');
		$exists = array_keys($list);
		$exists = array_map([Utils::class, 'dirname'], $exists);

		$db = DB::getInstance();

		$in_db = $db->getAssoc('SELECT dir_path, dir_path FROM web_pages;');

		$deleted = array_diff($in_db, $exists);
		$new = array_diff($exists, $in_db);
		$intersection = array_intersect($in_db, $exists);

		if ($deleted) {
			$db->exec(sprintf('DELETE FROM web_pages WHERE %s;', $db->where('dir_path', $deleted)));
		}

		sort($new);

		foreach ($new as $path) {
			$f = Files::get($path . '/index.txt');

			if (!$f) {
				// This is a directory without content, ignore
				continue;
			}

			try {
				self::fromFile($f)->save();
			}
			catch (ValidationException $e) {
				// Ignore validation errors, just don't add pages to index
				$errors[] = sprintf('Erreur à l\'import, page "%s": %s', str_replace(File::CONTEXT_WEB . '/', '', $f->parent), $e->getMessage());
			}
		}

		if (count($new) || count($deleted)) {
			Cache::clear();
		}

		foreach ($intersection as $path) {
			$file = Files::get($path . '/index.txt');
			$page = Web::get(substr($path, 4));
			self::loadFromFile($page, $file);
			$page->save();
		}

		return $errors;
	}
}
