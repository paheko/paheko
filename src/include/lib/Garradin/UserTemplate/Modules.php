<?php

namespace Garradin\UserTemplate;

use Garradin\Entities\Module;

use Garradin\Files\Files;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

use const Garradin\ROOT;

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
		$existing = DB::getInstance()->getAssoc(sprintf('SELECT id, name FROM %s;', Module::TABLE));
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
			$f->updateFromJSON();
			$f->save();
			$f->updateTemplates();
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

			if (!$m->updateFromJSON(false)) {
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

		if (!$module->updateFromJSON()) {
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
			$out[] = $module->fetch($snippet, $variables);
		}

		return $out;
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
}