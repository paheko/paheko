<?php

namespace Garradin\Web;

use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use Garradin\Web\Skeleton;
use Garradin\Files\Files;
use Garradin\API;
use Garradin\Config;
use Garradin\DB;
use Garradin\Plugin;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\ValidationException;
use Garradin\Membres\Session;

use KD2\DB\EntityManager as EM;

use const Garradin\{WWW_URI, ADMIN_URL, FILE_STORAGE_BACKEND, ROOT};

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

		$exists = array_flip(Files::callStorage('listDirectoriesRecursively', $path));

		$db = DB::getInstance();

		$in_db = $db->getGrouped('SELECT path, file_path, modified FROM web_pages;');

		$deleted = array_diff_key($in_db, $exists);
		$new = array_diff_key($exists, $in_db);

		if ($deleted) {
			$deleted = array_map(function ($page) {
				return $page->path;
			}, $deleted);

			$db->exec(sprintf('DELETE FROM web_pages WHERE %s;', $db->where('path', $deleted)));
		}

		foreach (array_keys($new) as $path) {
			$f = Files::get(File::CONTEXT_WEB . '/' . $path . '/index.txt');

			if (!$f) {
				// This is a directory without content, ignore
				continue;
			}

			try {
				Page::fromFile($f)->save();
			}
			catch (ValidationException $e) {
				// Ignore validation errors, just don't add pages to index
				$errors[] = sprintf('Erreur à l\'import, page "%s": %s', str_replace(File::CONTEXT_WEB . '/', '', $f->parent), $e->getMessage());
			}
		}

		return $errors;

		/*
		// There's no need for that sync as it is triggered when loading a Page entity!
		$intersection = array_intersect_key($in_db, $exists);
		foreach ($intersection as $page) {
			$file = Files::get($page->file_path);

			$modified = new \DateTime($page->modified);

			if ($modified == $file->modified) {
				continue;
			}

			$page = Web::get($page->path);
			$page->loadFromFile($file);
			$page->save();
		}
		 */
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

	static public function listAll(string $parent): array
	{
		$sql = 'SELECT * FROM @TABLE WHERE parent = ? ORDER BY title COLLATE NOCASE;';
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

		$path = DB::getInstance()->firstColumn('SELECT path FROM web_pages WHERE uri = ?;', Utils::dirname($uri));

		if (!$path) {
			return null;
		}

		return Files::getFromURI(File::CONTEXT_WEB . '/' . $path . '/' . Utils::basename($uri));
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
		elseif ($uri == 'favicon.ico') {
			header('Location: ' . Config::getInstance()->fileURL('favicon'), true);
			return;
		}
		elseif (substr($uri, 0, 6) === 'admin/') {
			http_response_code(404);
			throw new UserException('Cette page n\'existe pas.');
		}
		elseif (($file = Files::getFromURI($uri))
			|| ($file = self::getAttachmentFromURI($uri))) {
			$size = null;

			if ($file->image) {
				foreach ($_GET as $key => $v) {
					if (array_key_exists($key, File::ALLOWED_THUMB_SIZES)) {
						$size = $key;
						break;
					}
				}
			}

			$session = Session::getInstance();

			if (Plugin::fireSignal('http.request.file.before', compact('file', 'uri', 'session'))) {
				// If a plugin handled the request, let's stop here
				return;
			}

			if ($size) {
				$file->serveThumbnail($session, $size);
			}
			else {
				$file->serve($session, isset($_GET['download']) ? true : false);
			}

			Plugin::fireSignal('http.request.file.after', compact('file', 'uri', 'session'));

			return;
		}

		$site_disabled = Config::getInstance()->get('site_disabled');

		// Redirect old categories
		if (substr($uri, -1) == '/') {
			http_response_code(301);
			Utils::redirect('/' . rtrim($uri, '/'));
		}

		$page = null;

		if ($uri == '') {
			$skel = 'index.html';
		}
		elseif (!$site_disabled && ($page = self::getByURI($uri)) && $page->status == Page::STATUS_ONLINE) {
			$skel = $page->template();
			$page = $page->asTemplateArray();
		}
		else {
			// Trying to see if a custom template with this name exists
			if (preg_match('!^[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $uri)) {
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

		if ($site_disabled && ($skel == '404.html' || $uri == '')) {
			Utils::redirect(ADMIN_URL);
		}

		if (Plugin::fireSignal('http.request.skeleton.before', compact('page', 'skel', 'uri'))) {
			return;
		}

		$s = new Skeleton($skel);
		$s->serve(compact('uri', 'page', 'skel'));

		Plugin::fireSignal('http.request.skeleton.after', compact('page', 'skel', 'uri'));
	}
}
