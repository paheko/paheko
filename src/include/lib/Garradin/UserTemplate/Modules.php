<?php

namespace Garradin\UserTemplate;

use Garradin\Entities\Module;

use Garradin\Files\Files;
use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Users\Session;
use Garradin\Web\Web;
use Garradin\Entities\Web\Page;

use const Garradin\ROOT;
use const Garradin\ADMIN_URL;

use \KD2\DB\EntityManager as EM;

class Modules
{
	// Shortcuts so that code calling snippets method don't have to use Module entity
	const SNIPPET_TRANSACTION = Module::SNIPPET_TRANSACTION;
	const SNIPPET_USER = Module::SNIPPET_USER;
	const SNIPPET_HOME_BUTTON = Module::SNIPPET_HOME_BUTTON;

	/**
	 * Lists all modules from files and stores a cache
	 */
	static public function refresh(): void
	{
		$db = DB::getInstance();
		$existing = $db->getAssoc(sprintf('SELECT id, name FROM %s;', Module::TABLE));
		$list = self::listRaw();

		$create = array_diff($list, $existing);
		$delete = array_diff($existing, $list);
		$existing = array_diff($list, $create);

		foreach ($create as $name) {
			self::create($name);
		}

		foreach ($delete as $name) {
			self::get($name)->delete();
		}

		foreach ($existing as $name) {
			$f = self::get($name);
			$f->updateFromINI();
			$f->save();
			$f->updateTemplates();
		}

		if (!$db->test(Module::TABLE, 'web = 1 AND enabled = 1')) {
			$db->exec('UPDATE modules SET enabled = 1 WHERE id = (SELECT id FROM modules WHERE web = 1 LIMIT 1);');
		}
	}

	/**
	 * List modules names from locally installed directories
	 */
	static public function listRaw(bool $include_installed = true): array
	{
		$list = [];

		// First list modules bundled
		foreach (glob(Module::DIST_ROOT . '/*') as $file) {
			if (!is_dir($file)) {
				continue;
			}

			// Ignore test modules
			if (file_exists($file . '/ignore')) {
				continue;
			}

			$name = Utils::basename($file);
			$list[$name] = $name;
		}

		if ($include_installed) {
			// Then add modules in files
			foreach (Files::list(Module::ROOT) as $file) {
				if ($file->type != $file::TYPE_DIRECTORY) {
					continue;
				}

				$list[$file->name] = $file->name;
			}
		}

		sort($list);
		return $list;
	}

	/**
	 * List locally installed modules, directly from the filesystem, without creating them in the database cache
	 * (used in Install form)
	 */
	static public function listLocal(): array
	{
		$list = self::listRaw(false);
		$out = [];

		foreach ($list as $name) {
			$m = new Module;
			$m->name = $name;

			if (!$m->updateFromINI(false)) {
				continue;
			}

			$out[$name] = $m;
		}

		return $out;
	}

	static public function create(string $name): ?Module
	{
		$module = new Module;
		$module->name = $name;

		if (!$module->updateFromINI()) {
			return null;
		}

		$module->save();
		$module->updateTemplates();
		return $module;
	}

	/**
	 * List modules from the database
	 */
	static public function list(): array
	{
		return EM::getInstance(Module::class)->all('SELECT * FROM @TABLE ORDER BY label COLLATE NOCASE ASC;');
	}

	static public function snippetsAsString(string $snippet, array $variables = []): string
	{
		return implode("\n", self::snippets($snippet, $variables));
	}

	static public function snippets(string $snippet, array $variables = []): array
	{
		$out = [];

		foreach (self::listForSnippet($snippet) as $module) {
			$out[$module->name] = $module->fetch($snippet, $variables);
		}

		return array_filter($out, fn($a) => trim($a) !== '');
	}

	static public function listForSnippet(string $snippet): array
	{
		return EM::getInstance(Module::class)->all('SELECT f.* FROM @TABLE f
			INNER JOIN modules_templates t ON t.id_module = f.id
			WHERE t.name = ? AND f.enabled = 1
			ORDER BY f.label COLLATE NOCASE ASC;', $snippet);
	}

	static public function get(string $name): ?Module
	{
		return EM::findOne(Module::class, 'SELECT * FROM @TABLE WHERE name = ?;', $name);
	}

	static public function isEnabled(string $name): bool
	{
		return (bool) EM::getInstance(Module::class)->col('SELECT 1 FROM @TABLE WHERE name = ? AND enabled = 1;', $name);
	}

	static public function getWeb(): Module
	{
		$module = EM::findOne(Module::class, 'SELECT * FROM @TABLE WHERE web = 1 AND enabled = 1 LIMIT 1;');

		// Just in case
		if (!$module) {
			throw new \LogicException('No web module is enabled?!');
		}

		return $module;
	}

	static public function route(string $uri): void
	{
		$page = null;
		$path = null;
		$has_local_file = null;

		// We are looking for a module
		if (substr($uri, 0, 2) == 'm/') {
			$path = substr($uri, 2);
			$name = strtok($path, '/');
			$path = strtok(false);
			$module = self::get($name);

			if (!$module) {
				http_response_code(404);
				throw new UserException('This page does not exist.');
			}
		}
		// Or: we are looking for the "web" module
		else {
			// Redirect to ADMIN_URL if website is disabled
			// (but not for content.css)
			if (Config::getInstance()->site_disabled && $uri != 'content.css') {
				Utils::redirect(ADMIN_URL);
			}

			$module = self::getWeb();
		}

		// If path ends with trailing slash, then ask for index.html
		if (!$path || substr($path, -1) == '/') {
			$path .= 'index.html';
		}

		// Find out web path
		if ($module->web && $module->enabled && substr($uri, 0, 2) !== 'm/') {
			$uri = rawurldecode($uri);

			if ($uri == '') {
				$path = 'index.html';
			}
			elseif ($module->hasLocalFile($uri)) {
				$path = $uri;
				$has_local_file = true;
			}
			elseif (($page = Web::getByURI($uri)) && $page->status == Page::STATUS_ONLINE) {
				$path = $page->template();
				$page = $page->asTemplateArray();
			}
			else {
				$path = '404.html';
			}
		}
		// 404 if module is not enabled, except for icon
		elseif (!$module->enabled && !$module->system && $path != Module::ICON_FILE) {
			http_response_code(404);
			throw new UserException('This page is currently disabled.');
		}

		$has_local_file ??= $module->hasLocalFile($path);
		$has_dist_file = !$has_local_file && $module->hasDistFile($path);

		// Check if the file actually exists in the module
		if (!$has_local_file && !$has_dist_file) {
			http_response_code(404);
			throw new UserException('This page is not found, sorry.');
		}

		$module->serve($path, $has_local_file, compact('uri', 'page'));
	}
}
