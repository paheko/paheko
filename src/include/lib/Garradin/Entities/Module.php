<?php

namespace Garradin\Entities;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Plugins;
use Garradin\UserException;
use Garradin\Files\Files;
use Garradin\UserTemplate\UserTemplate;
use Garradin\Users\Session;
use Garradin\Web\Cache;

use Garradin\Entities\Files\File;

use const Garradin\{ROOT, WWW_URL};

class Module extends Entity
{
	const ROOT = File::CONTEXT_MODULES;
	const DIST_ROOT = ROOT . '/modules';
	const META_FILE = 'module.ini';
	const ICON_FILE = 'icon.svg';
	const README_FILE = 'README.md';
	const CONFIG_FILE = 'config.html';
	const INDEX_FILE = 'index.html';

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
	protected ?string $author;
	protected ?string $author_url;
	protected ?string $restrict_section;
	protected ?int $restrict_level;
	protected bool $home_button;
	protected bool $menu;
	protected ?\stdClass $config;
	protected bool $enabled;
	protected bool $web;

	/**
	 * System modules are always available, disabling them only hides the links
	 */
	protected bool $system;

	public function selfCheck(): void
	{
		$this->assert(preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/', $this->name), 'Nom unique de module invalide: ' . $this->name);
		$this->assert(trim($this->label) !== '', 'Le libellé ne peut rester vide');
	}

	/**
	 * Fills information from module.ini file
	 */
	public function updateFromINI(bool $use_local = true): bool
	{
		if ($use_local && ($file = Files::get($this->path(self::META_FILE)))) {
			$ini = $file->fetch();
			$from_dist = false;
		}
		elseif (file_exists($this->distPath(self::META_FILE))) {
			$ini = file_get_contents($this->distPath(self::META_FILE));
			$from_dist = true;
		}
		else {
			return false;
		}

		$ini = @parse_ini_string($ini, false, \INI_SCANNER_TYPED);

		if (empty($ini)) {
			return false;
		}

		$ini = (object) $ini;

		if (!isset($ini->name)) {
			return false;
		}

		$this->set('label', $ini->name);
		$this->set('description', $ini->description ?? null);
		$this->set('author', $ini->author ?? null);
		$this->set('author_url', $ini->author_url ?? null);
		$this->set('web', !empty($ini->web));
		$this->set('home_button', !empty($ini->home_button));
		$this->set('menu', !empty($ini->menu));
		$this->set('restrict_section', $ini->restrict_section ?? null);
		$this->set('restrict_level', isset($ini->restrict_section, $ini->restrict_level, Session::ACCESS_WORDS[$ini->restrict_level]) ? Session::ACCESS_WORDS[$ini->restrict_level] : null);

		if ($from_dist && !empty($ini->system)) {
			$this->set('system', true);
		}

		return true;
	}

	public function updateTemplates(): void
	{
		$check = self::SNIPPETS + [self::CONFIG_FILE => 'Config'];
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
		if (!$this->hasFile(self::ICON_FILE)) {
			return null;
		}

		return $this->url(self::ICON_FILE);
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
		return $this->hasLocalFile($file) || $this->hasDistFile($file);
	}

	public function hasDist(): bool
	{
		return file_exists($this->distPath());
	}

	public function hasLocal(): bool
	{
		return Files::exists($this->path());
	}

	public function hasLocalFile(string $path): bool
	{
		return Files::exists($this->path($path));
	}

	public function hasDistFile(string $path): bool
	{
		return file_exists($this->distPath($path));
	}

	public function fetchLocalFile(string $path): ?string
	{
		$file = Files::get($this->path($path));
		return !$file ? null : $file->fetch();
	}

	public function fetchDistFile(string $path): ?string
	{
		return @file_get_contents($this->distPath($path)) ?: null;
	}

	public function hasConfig(): bool
	{
		return DB::getInstance()->test('modules_templates', 'id_module = ? AND name = ?', $this->id(), self::CONFIG_FILE);
	}

	public function hasData(): bool
	{
		return DB::getInstance()->test('sqlite_master', 'type = \'table\' AND name = ?', sprintf('modules_data_%s', $this->name));
	}

	public function canDelete(): bool
	{
		return !empty($this->config) || $this->hasLocal() || $this->hasData();
	}

	public function listFiles(?string $path = null): array
	{
		$out = [];
		$base = File::CONTEXT_MODULES . '/' . $this->name;

		if ($path && false !== strpos($path, '..')) {
			return [];
		}

		$path = $path ? '/' . $path : '';

		foreach (Files::listForContext(File::CONTEXT_MODULES, $this->name . $path) as $file) {
			$_path = substr($file->path, strlen($base . '/'));

			$out[$file->name] = [
				'name'      => $file->name,
				'dir'       => $file->isDir(),
				'path'      => $_path,
				'file_path' => $file->path,
				'type'      => $file->mime,
				'local'     => true,
			];
		}

		$dist_path = $this->distPath(trim($path, '/'));

		if (is_dir($dist_path)) {
			foreach (scandir($dist_path) as $file) {
				if (substr($file, 0, 1) == '.') {
					continue;
				}

				if (isset($out[$file])) {
					$out[$file]['dist'] = true;
					continue;
				}

				$out[$file] = [
					'name'      => $file,
					'type'      => mime_content_type($dist_path . '/' . $file),
					'dir'       => is_dir($dist_path . '/' . $file),
					'path'      => $path . $file,
					'local'     => false,
					'dist'      => true,
					'file_path' => $base . '/' . $path . $file,
				];
			}
		}

		foreach ($out as &$file) {
			$file['editable'] = UserTemplate::isTemplate($file['path'])
				|| substr($file['type'], 0, 5) === 'text/'
				|| preg_match('/\.(?:json|md|skriv|html|css|js)$/', $file['name']);
			$file['open_url'] = '!common/files/preview.php?p=' . rawurlencode($file['file_path']);
			$file['edit_url'] = '!common/files/edit.php?p=' . rawurlencode($file['file_path']);
			$file['delete_url'] = '!common/files/delete.php?p=' . rawurlencode($file['file_path']);
		}

		unset($file);

		uasort($out, function ($a, $b) {
			if ($a['dir'] == $b['dir']) {
				return strnatcasecmp($a['name'], $b['name']);
			}
			elseif ($a['dir'] && !$b['dir']) {
				return -1;
			}
			else {
				return 1;
			}
		});


		return $out;
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

		if ($this->web && $this->enabled && !$file) {
			return WWW_URL;
		}

		return sprintf('%sm/%s/%s%s', WWW_URL, $this->name, $file, $params);
	}

	public function isValidPath(string $path): bool
	{
		return (bool) preg_match('!^(?:[\w\d_-]+/)*[\w\d_-]+(?:\.[\w\d_-]+)*$!i', $path);
	}

	public function validatePath(string $path): void
	{
		if (!$this->isValidPath($path)) {
			throw new \InvalidArgumentException('Invalid skeleton name');
		}
	}

	public function template(string $file)
	{
		if ($file == self::CONFIG_FILE) {
			Session::getInstance()->requireAccess(Session::SECTION_CONFIG, Session::ACCESS_ADMIN);
		}

		$this->validatePath($file);

		$ut = new UserTemplate($this->name . '/' . $file);
		$ut->assign('module', array_merge($this->asArray(false), ['url' => $this->url()]));

		return $ut;
	}

	public function fetch(string $file, array $params): string
	{
		$ut = $this->template($file);
		$ut->assignArray($params);
		return $ut->fetch();
	}

	public function serve(string $path, bool $has_local_file, array $params = []): void
	{
		if (UserTemplate::isTemplate($path)) {
			// Error if path is not valid
			// we allow any path for static files, but not for skeletons
			if (!$this->isValidPath($path)) {
				if ($this->web) {
					$path = '404.html';
				}
				else {
					http_response_code(404);
					throw new UserException('This address is invalid.');
				}
			}

			if ($this->web) {
				$this->serveWeb($path, $params);
				return;
			}
			else {
				$ut = $this->template($path);
				$ut->serve($params);
			}
		}
		// Serve a static file from a user module
		elseif ($has_local_file) {
			$file = Files::get(File::CONTEXT_MODULES . '/' . $this->name . '/' . $path);

			if (!$file) {
				throw new UserException('Invalid path');
			}

			$file->serve();
		}
		// Serve a static file from dist path
		else {
			$type = $this->getFileTypeFromExtension($path);
			$real_path = $this->distPath($path);

			// Create symlink to static file
			Cache::link($path, $real_path);

			http_response_code(200);
			header(sprintf('Content-Type: %s;charset=utf-8', $type), true);
			readfile($real_path);
			flush();
		}
	}

	public function serveWeb(string $path, array $params): void
	{
		$uri = $params['uri'] ?? null;

		// Fire signal before display of a web page
		$plugin_params = ['path' => $path, 'uri' => $uri, 'module' => $this];

		if (Plugins::fireSignal('web.request.before', $plugin_params)) {
			return;
		}

		$type = null;

		$ut = $this->template($path);
		$ut->assignArray($params);
		extract($ut->fetchWithType());

		if ($uri !== null && preg_match('!html|xml|text!', $type) && !$ut->get('nocache')) {
			$cache = true;
		}
		else {
			$cache = false;
		}

		$plugin_params['type'] = $type;
		$plugin_params['cache'] = $cache;

		// Call plugins, allowing them to modify the content
		if (Plugins::fireSignal('web.request', $plugin_params, $content)) {
			return;
		}

		header(sprintf('Content-Type: %s;charset=utf-8', $type), true);

		if ($type == 'application/pdf') {
			Utils::streamPDF($content);
		}
		else {
			echo $content;
		}

		if ($cache) {
			Cache::store($uri, $content);
		}

		Plugins::fireSignal('web.request.after', $plugin_params, $content);
	}

	public function getFileTypeFromExtension(string $path): ?string
	{
		$dot = strrpos($path, '.');

		// Templates with no extension are returned as HTML by default
		// unless {{:http type=...}} is used
		if ($dot === false) {
			return 'text/html';
		}

		// Templates with no extension are returned as HTML by default
		// unless {{:http type=...}} is used
		if ($dot === false) {
			return 'text/html';
		}

		$ext = substr($path, $dot+1);

		// Common types
		switch ($ext) {
			case 'txt':
				return 'text/plain';
			case 'html':
			case 'htm':
			case 'tpl':
			case 'btpl':
			case 'skel':
				return 'text/html';
			case 'xml':
				return 'text/xml';
			case 'css':
				return 'text/css';
			case 'js':
				return 'text/javascript';
			case 'png':
			case 'gif':
			case 'webp':
				return 'image/' . $ext;
			case 'svg':
				return 'image/svg+xml';
			case 'jpeg':
			case 'jpg':
				return 'image/jpeg';
			default:
				return null;
		}
	}
}
