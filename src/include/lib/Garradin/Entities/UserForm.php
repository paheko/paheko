<?php

namespace Garradin\Entities;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Files\Files;
use Garradin\UserTemplate\UserTemplate;
use Garradin\Users\Session;

use Garradin\Entities\Files\File;

use const Garradin\{ROOT, WWW_URL};

class UserForm extends Entity
{
	const ROOT = File::CONTEXT_SKELETON . '/forms';
	const DIST_ROOT = ROOT . '/skel-dist/forms';
	const META_FILE = 'form.json';

	const CONFIG_TEMPLATE = 'config.html';

	const SNIPPET_TRANSACTION = 'snippets/transaction_details.html';
	const SNIPPET_USER = 'snippets/user_details.html';
	const SNIPPET_HOME_ICON = 'snippets/home_icon.html';

	const SNIPPETS = [
		self::SNIPPET_HOME_ICON => 'Icône sur la page d\'accueil',
		self::SNIPPET_USER => 'En bas de la fiche d\'un membre',
		self::SNIPPET_TRANSACTION => 'En bas de la fiche d\'une écriture',
	];

	const TABLE = 'user_forms';

	protected ?int $id;

	/**
	 * Directory name
	 */
	protected string $name;

	protected string $label;
	protected ?string $description;
	protected ?\stdClass $config;
	protected bool $enabled;

	public function selfCheck(): void
	{
		$this->assert(preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/', $this->name), 'Nom unique de formulaire invalide: ' . $this->name);
		$this->assert(trim($this->label) !== '', 'Le libellé ne peut rester vide');
	}

	/**
	 * Fills information from form.json file
	 */
	public function updateFromJSON(): bool
	{
		if ($file = Files::get($this->path(self::META_FILE))) {
			$json = $file->fetch();
		}
		elseif (file_exists($this->distPath(self::META_FILE))) {
			$json = file_get_contents($this->distPath(self::META_FILE));
		}
		else {
			return false;
		}

		$json = json_decode($json);

		if (!isset($json->label)) {
			return false;
		}

		$this->set('label', $json->label);
		$this->set('description', $json->description ?? null);

		return true;
	}

	public function updateTemplates(): void
	{
		$check = self::SNIPPETS + [self::CONFIG_TEMPLATE => 'Config'];
		$templates = [];
		$db = DB::getInstance();

		$db->begin();
		$db->delete('user_forms_templates', 'id_form = ' . (int)$this->id());

		foreach ($check as $file => $label) {
			if (Files::exists($this->path($file)) || file_exists($this->distPath($file))) {
				$templates[] = $file;
				$db->insert('user_forms_templates', ['id_form' => $this->id(), 'name' => $file]);
			}
		}

		$db->commit();
	}

	public function path(string $file = null): string
	{
		return self::ROOT . '/' . $this->name . ($file ? '/' . $file : '');
	}

	public function distPath(string $file = null): string
	{
		return ROOT . '/skel-dist/forms/' . $this->name . ($file ? '/' . $file : '');
	}

	public function dir(): ?File
	{
		return Files::get(self::ROOT . $this->name);
	}

	public function hasDist(): bool
	{
		return file_exists($this->distPath());
	}

	public function hasConfig(): bool
	{
		return DB::getInstance()->test('user_forms_templates', 'id_form = ? AND name = ?', $this->id(), self::CONFIG_TEMPLATE);
	}

	public function canDelete(): bool
	{
		return $this->dir() ? true : false;
	}

	public function delete(): bool
	{
		$dir = $this->dir();

		if ($dir) {
			$dir->delete();
		}

		DB::getInstance()->exec(sprintf('DROP TABLE IF EXISTS forms_%s', $this->name));

		return parent::delete();
	}

	public function url(string $file = '', array $params = null)
	{
		if (null !== $params) {
			$params = '?' . http_build_query($params);
		}

		return sprintf('%sform/%s/%s%s', WWW_URL, $this->name, $file, $params);
	}

	public function serve(string $file)
	{
		$this->template($file)->serve();
	}

	public function fetch(string $file, array $variables = [])
	{
		try {
			$t = $this->template($file);
			$t->assignArray($variables);
			return $t->fetch();
		}
		catch (Brindille_Exception $e) {
			return sprintf('<div style="border: 5px solid orange; padding: 10px; background: yellow;"><h2>Erreur dans le code du document</h2><p>%s</p></div>', nl2br(htmlspecialchars($e->getMessage())));
		}
	}

	public function template(string $file)
	{
		if ($file == self::CONFIG_TEMPLATE) {
			Session::getInstance()->requireAccess(Session::SECTION_CONFIG, Session::ACCESS_ADMIN);
		}

		if (!preg_match('!^(?:snippets/)?[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $file)) {
			throw new \InvalidArgumentException('Invalid skeleton name');
		}

		$ut = new UserTemplate('forms/' . $this->name . '/' . $file);
		$ut->assign('form', $this->asArray(false) + ['url' => $this->url()]);

		return $ut;
	}

}
