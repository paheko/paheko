<?php

namespace Garradin\Web;

use Garradin\DB;
use Garradin\Utils;
use Garradin\ValidationException;

use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use Garradin\Files\Files;

use const Garradin\FILE_STORAGE_BACKEND;

/**
 * This is used to sync from index.txt files
 * @todo FIXME TODO remove in Paheko 1.4
 * @deprecated Only used to migrate from versions < 1.3
 */
class Sync
{
	static protected function importFromRaw(string $str): bool
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

				if (!array_key_exists($value, self::FORMATS_LIST)) {
					throw new \LogicException('Unknown format: ' . $value);
				}

				$page->set('format', $value);
			}
			elseif ($key == 'type') {
				$value = strtolower($value);

				if ($value == strtolower(self::TYPES[self::TYPE_CATEGORY])) {
					$value = self::TYPE_CATEGORY;
				}
				elseif ($value == strtolower(self::TYPES[self::TYPE_PAGE])) {
					$value = self::TYPE_PAGE;
				}
				else {
					throw new \LogicException('Unknown type: ' . $value);
				}

				$page->set('type', $value);
			}
			elseif ($key == 'status') {
				$value = strtolower($value);

				if (!array_key_exists($value, self::STATUS_LIST)) {
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
		if (!self::importFromRaw($page, $file->fetch())) {
			throw new \LogicException('Invalid page content: ' . $file->parent);
		}

		if (empty($page->modified)) {
			$page->set('modified', $file->modified);
		}

		if (!isset($page->type) || $page->type != self::TYPE_CATEGORY) {
			$page->set('type', $page->checkRealType());
		}
		else {
			$page->set('type', self::TYPE_CATEGORY);
		}
	}

	static public function fromFile(File $file): self
	{
		$page = new Page;

		$page->importForm([
			'path' => substr($file->parent, strlen(File::CONTEXT_WEB . '/')),
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
		$exists = array_map([Utils::class, 'basename'], $exists);

		$db = DB::getInstance();

		$in_db = $db->getAssoc('SELECT dir_path, dir_path FROM web_pages;');

		$deleted = array_diff($in_db, $exists);
		$new = array_diff($exists, $in_db);

		if ($deleted) {
			$db->exec(sprintf('DELETE FROM web_pages WHERE %s;', $db->where('dir_path', $deleted)));
		}

		$new = array_keys($new);
		ksort($new);

		foreach ($new as $path) {
			$f = Files::get(File::CONTEXT_WEB . '/' . $path . '/index.txt');

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

		// There's no need for that sync as it is triggered when loading a Page entity!
		$intersection = array_intersect_key($in_db, $exists);
		foreach ($intersection as $page) {
			$file = Files::get($page->dir_path);
			$page = Web::get($page->path);
			self::loadFromFile($page, $file);
			$page->save();
		}

		return $errors;
	}
}
