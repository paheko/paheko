<?php

namespace Garradin\UserTemplate;

use Garradin\Entities\UserForm;

use Garradin\Files\Files;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

use const Garradin\ROOT;

use \KD2\DB\EntityManager as EM;

class UserForms
{
	/**
	 * Lists all forms from files and stores a cache
	 */
	static public function refresh(): void
	{
		$existing = DB::getInstance()->getAssoc(sprintf('SELECT id, name FROM %s;', UserForm::TABLE));
		$list = [];

		foreach (Files::list(UserForm::ROOT) as $file) {
			if ($file->type != $file::TYPE_DIRECTORY) {
				continue;
			}

			$list[] = $file->name;
		}

		foreach (glob(UserForm::DIST_ROOT . '/*') as $file) {
			if (!is_dir($file)) {
				continue;
			}

			$list[] = Utils::basename($file);
		}

		$list = array_unique($list);
		sort($list);

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

	static public function create(string $name): ?UserForm
	{
		$uf = new UserForm;
		$uf->name = $name;

		if (!$uf->updateFromJSON()) {
			return null;
		}

		$uf->save();
		$uf->updateTemplates();
		return $uf;
	}

	static public function list(): array
	{
		return EM::getInstance(UserForm::class)->all('SELECT * FROM @TABLE ORDER BY label COLLATE NOCASE ASC;');
	}

	static public function getSnippets(string $snippet, array $variables = []): string
	{
		$out = '';

		foreach (self::listForSnippet($snippet) as $form) {
			$out .= $form->fetch($snippet, $variables);
			$out .= PHP_EOL;
		}

		return $out;
	}

	static public function listForSnippet(string $snippet): array
	{
		return EM::getInstance(UserForm::class)->all('SELECT f.* FROM @TABLE f
			INNER JOIN user_forms_templates t ON t.id_form = f.id
			WHERE t.name = ? AND f.enabled = 1
			ORDER BY f.label COLLATE NOCASE ASC;', $snippet);
	}

	static public function get(string $name): ?UserForm
	{
		return EM::findOne(UserForm::class, 'SELECT * FROM @TABLE WHERE name = ?;', $name);
	}

	static public function serve(string $uri): void
	{
		$name = substr($uri, 0, strrpos($uri, '/'));
		$file = substr($uri, strrpos($uri, '/') + 1) ?: 'index.html';

		$form = self::get($name);

		if (!$form || !$form->enabled) {
			http_response_code(404);
			throw new UserException('Ce formulaire n\'existe pas');
		}

		$form->displayWeb($file);
	}

}