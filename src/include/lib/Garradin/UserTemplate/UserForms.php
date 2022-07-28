<?php

namespace Garradin;

use Garradin\Entities\UserForm;

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

		foreach (glob(self::DIST_SKEL_ROOT . '/*') as $file) {
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

	static public function listForSnippet(string $snippet): array
	{
		return EM::getInstance(UserForm::class)->all('SELECT * FROM @TABLE
			WHERE templates LIKE ?
			ORDER BY label COLLATE NOCASE ASC;', sprintf('%%"%s%"%', $snippet));
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

		if (!$form) {
			http_response_code(404);
			throw new UserException('Ce formulaire n\'existe pas');
		}

		$form->displayWeb($file);
	}

}