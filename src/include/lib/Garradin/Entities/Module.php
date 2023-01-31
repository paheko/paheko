<?php

namespace Garradin\Entities;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Files\Files;
use Garradin\UserTemplate\UserTemplate;
use Garradin\Users\Session;

use Garradin\Entities\Files\File;

use const Garradin\{ROOT, WWW_URL};

class Module extends Entity
{
	const ROOT = File::CONTEXT_SKELETON . '/modules';
	const DIST_ROOT = ROOT . '/skel-dist/modules';
	const META_FILE = 'module.json';

	const CONFIG_TEMPLATE = 'config.html';

	// Snippets, don't forget to create alias constant in UserTemplate\Modules class
	const SNIPPET_TRANSACTION = 'snippets/transaction_details.html';
	const SNIPPET_USER = 'snippets/user_details.html';
	const SNIPPET_HOME_BUTTON = 'snippets/home_button.html';

	const SNIPPETS = [
		self::SNIPPET_HOME_BUTTON => 'Icône sur la page d\'accueil',
		self::SNIPPET_USER => 'En bas de la fiche d\'un membre',
		self::SNIPPET_TRANSACTION => 'En bas de la fiche d\'une écriture',
	];

	const TABLE = 'modules';

	protected ?int $id;

	/**
	 * Directory name
	 */
	protected string $name;

	protected string $label;
	protected ?string $description;
	protected ?string $config;
	protected bool $enabled;

	public function selfCheck(): void
	{
		$this->assert(preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/', $this->name), 'Nom unique de module invalide: ' . $this->name);
		$this->assert(trim($this->label) !== '', 'Le libellé ne peut rester vide');
	}

	/**
	 * Fills information from module.json file
	 */
	public function updateFromJSON(bool $loadConfig = false): bool
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
		if ($loadConfig) {
			$this->setConfig(get_object_vars($json));
		}
		return true;
	}

	public function setConfig(array $config): void
	{
		$tmp = [];
		foreach($config as $configKey => $value) {
			if (is_string($value) && $configKey !== 'label' && $configKey !== 'description') // black-list would be welcomed
				$tmp[$configKey] = $value;
		}
		$this->set('config', json_encode($tmp));
	}

	public function updateTemplates(): void
	{
		$check = self::SNIPPETS + [self::CONFIG_TEMPLATE => 'Config'];
		$templates = [];
		$db = DB::getInstance();

		$db->begin();
		$db->delete('modules_templates', 'id_module = ' . (int)$this->id());

		foreach ($check as $file => $label) {
			if (Files::exists($this->path($file)) || file_exists($this->distPath($file))) {
				$templates[] = $file;
				$db->insert('modules_templates', ['id_module' => $this->id(), 'name' => $file]);
			}
		}

		$db->commit();
	}

	public function icon_url(): ?string
	{
		if (!$this->hasFile('icon.svg')) {
			return null;
		}

		return $this->url('icon.svg');
	}

	public function path(string $file = null): string
	{
		return self::ROOT . '/' . $this->name . ($file ? '/' . $file : '');
	}

	public function distPath(string $file = null): string
	{
		return self::DIST_ROOT . '/' . $this->name . ($file ? '/' . $file : '');
	}

	public function dir(): ?File
	{
		return Files::get(self::ROOT . $this->name);
	}

	public function hasFile(string $file): bool
	{
		if (Files::exists($this->path($file))) {
			return true;
		}

		if (file_exists($this->distPath($file))) {
			return true;
		}

		return false;
	}

	public function hasDist(): bool
	{
		return file_exists($this->distPath());
	}

	public function hasConfig(): bool
	{
		return DB::getInstance()->test('modules_templates', 'id_module = ? AND name = ?', $this->id(), self::CONFIG_TEMPLATE);
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

		DB::getInstance()->exec(sprintf('DROP TABLE IF EXISTS modules_data_%s', $this->name));

		return parent::delete();
	}

	public function url(string $file = '', array $params = null)
	{
		if (null !== $params) {
			$params = '?' . http_build_query($params);
		}

		return sprintf('%sm/%s/%s%s', WWW_URL, $this->name, $file, $params);
	}

	public function validateFileName(string $file)
	{
		if (!preg_match('!^(?:snippets/)?[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $file)) {
			throw new \InvalidArgumentException('Invalid skeleton name');
		}
	}

	public function template(string $file)
	{
		if ($file == self::CONFIG_TEMPLATE) {
			Session::getInstance()->requireAccess(Session::SECTION_CONFIG, Session::ACCESS_ADMIN);
		}

		$this->validateFileName($file);

		$ut = new UserTemplate('modules/' . $this->name . '/' . $file);
		$moduleVars = array_merge($this->asArray(false), ['url' => $this->url()]);
		$moduleVars['config'] = json_decode($this->config, true);
		$ut->assign('module', $moduleVars);

		return $ut;
	}

	public function fetch(string $file, array $params): string
	{
		$ut = $this->template($file);
		$ut->assignArray($params);
		return $ut->fetch();
	}
}