<?php

namespace Paheko\UserTemplate;

use Paheko\Entities\Module;

use Paheko\Files\Files;
use Paheko\Config;
use Paheko\DB;
use Paheko\Utils;
use Paheko\ValidationException;
use Paheko\UserException;
use Paheko\Users\Session;
use Paheko\Web\Web;
use Paheko\Entities\Files\File;
use Paheko\Entities\Users\User;
use Paheko\Entities\Web\Page;

use const Paheko\ROOT;
use const Paheko\ADMIN_URL;

use KD2\DB\EntityManager as EM;
use KD2\ZipReader;

class Modules
{
	// Shortcuts so that code calling snippets method don't have to use Module entity
	const SNIPPET_TRANSACTION = Module::SNIPPET_TRANSACTION;
	const SNIPPET_USER = Module::SNIPPET_USER;
	const SNIPPET_HOME_BUTTON = Module::SNIPPET_HOME_BUTTON;
	const SNIPPET_MY_SERVICES = Module::SNIPPET_MY_SERVICES;
	const SNIPPET_MY_DETAILS = Module::SNIPPET_MY_DETAILS;

	static public function fetchDistFile(string $path): ?string
	{
		if (substr($path, 0, strlen('modules/')) === 'modules/') {
			$path = substr($path, strlen('modules/'));
		}

		if (false !== strpos($path, '..')) {
			return null;
		}

		return @file_get_contents(Module::DIST_ROOT . '/' . $path) ?: null;
	}

	/**
	 * Lists all modules from files and stores a cache
	 */
	static public function refresh(): array
	{
		$db = DB::getInstance();
		$existing = $db->getAssoc(sprintf('SELECT id, name FROM %s;', Module::TABLE));
		$list = self::listRaw();

		$create = array_diff($list, $existing);
		$delete = array_diff($existing, $list);
		$existing = array_diff($list, $create);

		$errors = [];

		foreach ($create as $name) {
			try {
				self::create($name);
			}
			catch (ValidationException $e) {
				$errors[] = $name . ': ' . $e->getMessage();
			}
		}

		foreach ($delete as $name) {
			self::get($name)->delete();
		}

		foreach ($existing as $name) {
			try {
				$f = self::get($name);
				$f->updateFromINI();
				$f->save();
				$f->updateTemplates();
			}
			catch (ValidationException $e) {
				$errors[] = $name . ': ' . $e->getMessage();
			}
		}

		if (!$db->test(Module::TABLE, 'web = 1 AND enabled = 1')) {
			$db->exec('UPDATE modules SET enabled = 1 WHERE id = (SELECT id FROM modules WHERE web = 1 ORDER BY system DESC LIMIT 1);');
		}

		return $errors;
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

		foreach ($variables as &$var) {
			if (is_object($var) && $var instanceof User) {
				$var = $var->asModuleArray();
			}
		}

		unset($var);

		foreach (self::listForSnippet($snippet) as $module) {
			// Maybe the cache was wrong and the template doesn't exist anymore
			if (!$module->hasFile($snippet)) {
				continue;
			}

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
		$has_dist_file = null;

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
			elseif ($module->hasDistFile($uri)) {
				$path = $uri;
				$has_dist_file = true;
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
		$has_dist_file ??= !$has_local_file && $module->hasDistFile($path);

		// Check if the file actually exists in the module
		if (!$has_local_file && !$has_dist_file) {

			http_response_code(404);
			throw new UserException('This path does not exist, sorry.');
		}

		$module->serve($path, $has_local_file, compact('uri', 'page'));
	}

	static public function import(string $path, bool $overwrite = false): ?Module
	{
		$zip = new ZipReader;

		try {
			$zip->open($path);
		}
		catch (\OutOfBoundsException $e) {
			throw new \InvalidArgumentException('Invalid ZIP file: ' . $e->getMessage(), 0, $e);
		}

		$module_name = null;
		$files = [];

		foreach ($zip->iterate() as $name => $file) {
			if ($name == 'modules' || $file['dir']) {
				continue;
			}

			if (strpos($name, 'modules/') !== 0) {
				throw new \InvalidArgumentException('Invalid ZIP file: invalid path:' . $name);
			}

			$_mod = strtok(substr($name, strlen('modules/')), '/');

			if (!$module_name) {
				if (!$_mod || !preg_match(Module::VALID_NAME_REGEXP, $_mod)) {
					throw new \InvalidArgumentException('Invalid module name (allowed: [a-z][a-z0-9]*(_[a-z0-9])*): ' . $_mod);
				}

				$module_name = $_mod;
			}
			elseif ($module_name !== $_mod) {
				throw new \InvalidArgumentException('Two different modules names found.');
			}

			$_name = strtok(false);
			$files[$_name] = $name;
		}

		if (!$module_name || !count($files)) {
			throw new \InvalidArgumentException('No module found in archive');
		}

		$base = File::CONTEXT_MODULES . '/' . $module_name;

		if (Files::exists($base) && !$overwrite) {
			return null;
		}

		try {
			$module = self::get($module_name) ?? self::create($module_name);

			if (!$module) {
				throw new \InvalidArgumentException('Invalid module information');
			}

			foreach ($files as $local_name => $source) {
				$content = $zip->fetch($source);

				// Don't store file if it already exists and is the same
				if ($module->hasLocalFile($local_name) && ($file = Files::get($base . '/' . $local_name))) {
					if ($file->md5 == md5($content)) {
						continue;
					}
				}

				// Same for dist file
				if ($dist_file = $module->fetchDistFile($local_name)) {
					if (md5($dist_file) == md5($content)) {
						continue;
					}
				}

				Files::createFromString($base  . '/' . $local_name, $content);
			}

			return $module;
		}
		catch (ValidationException $e) {
			$dir = Files::get($base);

			// Delete any extracted files so far
			if ($dir) {
				$dir->delete();
			}

			throw new \InvalidArgumentException('Invalid file: ' . $e->getMessage(), 0, $e);
		}
		catch (\Exception $e) {
			$dir = Files::get($base);

			// Delete any extracted files so far
			if ($dir) {
				$dir->delete();
			}

			throw $e;
		}
		finally {
			unset($zip);
		}
	}
}
