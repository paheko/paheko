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
use KD2\ErrorManager;

class Modules
{
	// Shortcuts so that code calling snippets method don't have to use Module entity
	const SNIPPET_TRANSACTION = Module::SNIPPET_TRANSACTION;
	const SNIPPET_USER = Module::SNIPPET_USER;
	const SNIPPET_HOME_BUTTON = Module::SNIPPET_HOME_BUTTON;
	const SNIPPET_MY_SERVICES = Module::SNIPPET_MY_SERVICES;
	const SNIPPET_MY_DETAILS = Module::SNIPPET_MY_DETAILS;
	const SNIPPET_BEFORE_NEW_TRANSACTION = Module::SNIPPET_BEFORE_NEW_TRANSACTION;
	const SNIPPET_MARKDOWN_EXTENSION = Module::SNIPPET_MARKDOWN_EXTENSION;

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
		$db->begin();

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
			self::get($name, true)->delete();
		}

		foreach ($existing as $name) {
			try {
				$f = self::get($name);
				$f->updateFromINI();
				$f->selfCheck();
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

		$db->commit();

		return $errors;
	}

	static public function refreshEnabledModules(): void
	{
		$db = DB::getInstance();
		$db->begin();

		foreach (self::list() as $module) {
			try {
				$module->updateFromINI();
				$module->save();
				$module->updateTemplates();
			}
			catch (ValidationException $e) {
				// Ignore errors here
			}
		}

		$db->commit();
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
		$out = [];
		$i = EM::getInstance(Module::class)->iterate('SELECT * FROM @TABLE ORDER BY label COLLATE NOCASE ASC;');

		foreach ($i as $module) {
			if ($module->isValid()) {
				$out[] = $module;
			}
		}

		return $out;
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

			try {
				$content = $module->fetch($snippet, $variables);
			}
			catch (\RuntimeException $e) {
				ErrorManager::reportExceptionSilent($e);
				$content = sprintf('Le module "%s" a rencontré une erreur.', $module->name);
			}

			$out[$module->name] = $content;
		}

		return array_filter($out, fn($a) => trim($a) !== '');
	}

	static public function listForSnippet(string $snippet): array
	{
		$out = [];

		$i = EM::getInstance(Module::class)->iterate('SELECT f.* FROM @TABLE f
			INNER JOIN modules_templates t ON t.id_module = f.id
			WHERE t.name = ? AND f.enabled = 1
			ORDER BY f.label COLLATE NOCASE ASC;', $snippet);

		foreach ($i as $module) {
			if ($module->isValid()) {
				$out[] = $module;
			}
		}

		return $out;
	}

	static public function get(string $name, bool $return_invalid = false): ?Module
	{
		if (!$return_invalid && !preg_match(Module::VALID_NAME_REGEXP, $name)) {
			return null;
		}

		return EM::findOne(Module::class, 'SELECT * FROM @TABLE WHERE name = ?;', $name);
	}

	static public function isEnabled(string $name): bool
	{
		if (!preg_match(Module::VALID_NAME_REGEXP, $name)) {
			return false;
		}

		return (bool) EM::getInstance(Module::class)->col('SELECT 1 FROM @TABLE WHERE name = ? AND enabled = 1;', $name);
	}

	static public function getWeb(): Module
	{
		$module = EM::findOne(Module::class, 'SELECT * FROM @TABLE WHERE web = 1 AND enabled = 1 LIMIT 1;');

		// Just in case
		if (!$module) {
			$module = EM::findOne(Module::class, 'SELECT * FROM @TABLE WHERE web = 1 LIMIT 1;');

			if (!$module) {
				// Maybe we need to rescan modules?
				self::refresh();
				$module = EM::findOne(Module::class, 'SELECT * FROM @TABLE WHERE web = 1 LIMIT 1;');

				if (!$module) {
					throw new \LogicException('No web module exists');
				}
			}

			$module->set('enabled', true);
			$module->save();
		}

		$module->assertIsValid();

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
			$path = strtok('');
			$module = self::get($name);

			if (!$module) {
				throw new UserException('This page does not exist.', 404);
			}
		}
		// Or: we are looking for the "web" module
		else {
			$module = self::getWeb();
		}

		// Make sure the module name is valid
		$module->assertIsValid();

		// If path ends with trailing slash, then ask for index.html
		if (!$path || substr($path, -1) == '/') {
			$path .= 'index.html';
		}

		$name = Utils::basename($uri);

		// Do not expose templates if the name begins with an underscore
		// this is not really a security issue, but they will probably fail
		if (substr($name, 0, 1) === '_' || $name === Module::META_FILE) {
			throw new UserException('This address is private', 403);
		}

		$session = Session::getInstance();

		// Find out web path
		if ($module->web && $module->enabled && substr($uri, 0, 2) !== 'm/') {
			$uri = rawurldecode($uri);
			$path = '404.html';

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
			elseif ($page = Web::getByURI($uri)) {
				$status = $page->inherited_status;

				if ($status === Page::STATUS_DRAFT) {
					$path = '404.html';
				}
				elseif ($status === Page::STATUS_PRIVATE && !$session->isLogged()) {
					Utils::redirect('!login.php?p=1&r=' . Utils::getRequestURI());
				}
				else {
					$path = $page->template();
					$page = $page->asTemplateArray();
				}
			}
		}
		// 404 if module is not enabled, except for icon
		elseif (!$module->enabled && !$module->system && $path != Module::ICON_FILE) {
			http_response_code(404);
			throw new UserException('This page is currently disabled.');
		}

		// Restrict access
		if (isset($module->restrict_section, $module->restrict_level)) {
			if (!$session->isLogged()) {
				Utils::redirect('!login.php');
			}

			$session->requireAccess($module->restrict_section, $module->restrict_level);
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
				continue;
			}

			$_mod = strtok(substr($name, strlen('modules/')), '/');
			$_name = strtok('');

			if (!$module_name) {
				if (!$_mod || !preg_match(Module::VALID_NAME_REGEXP, $_mod)) {
					throw new \InvalidArgumentException('Invalid module name (allowed: [a-z][a-z0-9]*(_[a-z0-9])*): ' . $_mod);
				}

				$module_name = $_mod;
			}
			elseif ($module_name !== $_mod) {
				throw new \InvalidArgumentException('Two different modules names found.');
			}

			$files[$_name] = $name;
		}

		if (!$module_name || !count($files)) {
			throw new \InvalidArgumentException('No module found in archive');
		}

		if (!array_key_exists('module.ini', $files)) {
			throw new \InvalidArgumentException('Missing "module.ini" file in module');
		}

		$base = File::CONTEXT_MODULES . '/' . $module_name;

		if (Files::exists($base) && !$overwrite) {
			return null;
		}

		try {
			$module = self::get($module_name);

			if (!$module) {
				$module = new Module;
				$module->name = $module_name;
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

			if (!$module->updateFromINI()) {
				throw new ValidationException('Le fichier module.ini est invalide.');
			}

			$module->save();
			$module->updateTemplates();

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
