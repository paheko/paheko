<?php

namespace Paheko\Entities;

use Paheko\Entity;
use Paheko\DB;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\Files\Files;
use Paheko\UserTemplate\UserTemplate;
use Paheko\Users\Session;
use Paheko\Web\Cache;
use Paheko\Web\Router;

use KD2\ZipWriter;

use Paheko\Entities\Files\File;

use const Paheko\{ROOT, WWW_URL, BASE_URL};

class Module extends Entity
{
	const ROOT = File::CONTEXT_MODULES;
	const DIST_ROOT = ROOT . '/modules';
	const META_FILE = 'module.ini';
	const ICON_FILE = 'icon.svg';
	const CONFIG_FILE = 'config.html';
	const INDEX_FILE = 'index.html';
	const README_FILE = 'README.md';

	// Snippets, don't forget to create alias constant in UserTemplate\Modules class
	const SNIPPET_TRANSACTION = 'snippets/transaction_details.html';
	const SNIPPET_USER = 'snippets/user_details.html';
	const SNIPPET_HOME_BUTTON = 'snippets/home_button.html';
	const SNIPPET_MY_SERVICES = 'snippets/my_services.html';
	const SNIPPET_MY_DETAILS = 'snippets/my_details.html';
	const SNIPPET_BEFORE_NEW_TRANSACTION = 'snippets/transaction_new.html';

	const SNIPPETS = [
		self::SNIPPET_HOME_BUTTON => 'icône sur la page d\'accueil',
		self::SNIPPET_USER => 'en bas de la fiche d\'un membre',
		self::SNIPPET_TRANSACTION => 'en bas de la fiche d\'une écriture',
		self::SNIPPET_MY_SERVICES => 'sur la page "Mes activités"',
		self::SNIPPET_MY_DETAILS => 'sur la page "Mes infos personnelles"',
		self::SNIPPET_BEFORE_NEW_TRANSACTION => 'avant le formulaire de saisie d\'écriture',
	];

	const VALID_NAME_REGEXP = '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/';

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
		$this->assert(preg_match(self::VALID_NAME_REGEXP, $this->name), 'Nom unique de module invalide: ' . $this->name);
		$this->assert(trim($this->label) !== '', 'Le libellé ne peut rester vide');
		$this->assert(!isset($this->author_url) || preg_match('!^(?:https?://|mailto:)!', $this->author_url), 'L\'adresse du site de l\'auteur est invalide');
		$this->assert(!isset($this->restrict_section) || in_array($this->restrict_section, Session::SECTIONS, true), 'Restriction de section invalide');
		$this->assert(!isset($this->restrict_level) || in_array($this->restrict_level, Session::ACCESS_LEVELS, true), 'Restriction de niveau invalide');

		if (!$this->exists()) {
			$this->assert(!DB::getInstance()->test(self::TABLE, 'name = ?', $this->name), 'Un module existe déjà avec ce nom unique');
		}
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['restrict'])) {
			$this->set('restrict_section', strtok($source['restrict'], '_') ?: null);
			$this->set('restrict_level', (int)strtok(false) ?: null);
		}

		parent::importForm($source);
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

		$ini = Utils::parse_ini_string($ini, false, \INI_SCANNER_TYPED);

		if (empty($ini)) {
			return false;
		}

		$ini = (object) $ini;

		if (!isset($ini->name)) {
			return false;
		}

		$restrict_section = null;
		$restrict_level = null;

		if (isset($ini->restrict_section, $ini->restrict_level)
			&& array_key_exists($ini->restrict_level, Session::ACCESS_LEVELS)
			&& in_array($ini->restrict_section, Session::SECTIONS)) {
			$restrict_section = $ini->restrict_section;
			$restrict_level = Session::ACCESS_LEVELS[$ini->restrict_level];
		}

		$this->set('label', $ini->name);
		$this->set('description', $ini->description ?? null);
		$this->set('author', $ini->author ?? null);
		$this->set('author_url', $ini->author_url ?? null);
		$this->set('web', !empty($ini->web));
		$this->set('home_button', !empty($ini->home_button));
		$this->set('menu', !empty($ini->menu));
		$this->set('restrict_section', $restrict_section);
		$this->set('restrict_level', $restrict_level);

		if ($from_dist && !empty($ini->system)) {
			$this->set('system', true);
		}

		return true;
	}

	public function exportToIni(): void
	{
		$ini = '';

		foreach ($this->asArray() as $key => $value) {
			if ($key == 'name' || $key == 'id') {
				continue;
			}

			if ($key == 'label') {
				$key = 'name';
			}

			if ($key == 'restrict_level') {
				$value = array_search($value, Session::ACCESS_LEVELS);
			}

			if (is_bool($value)) {
				$value = $value ? 'true' : 'false';
			}
			elseif (null === $value || trim($value) === '') {
				$value = 'null';
			}
			elseif (is_string($value)) {
				$value = strtr($value, ['"' => '\\"', "'" => "\\'", '$' => '\\$']);
				$value = '"' . $value . '"';
			}

			$ini .= sprintf("%s = %s\n", $key, $value);
		}

		Files::createFromString($this->path('module.ini'), $ini);
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

	public function config_url(): ?string
	{
		if (!$this->hasFile(self::CONFIG_FILE)) {
			return null;
		}

		return $this->url(self::CONFIG_FILE);
	}

	public function storage_root(): string
	{
		return File::CONTEXT_EXTENSIONS . '/m/' . $this->name;
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
		return Files::get($this->path());
	}

	public function storage(): ?File
	{
		return Files::get($this->storage_root());
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
		return @file_exists($this->distPath($path));
	}

	public function fetchFile(string $path): ?string
	{
		if ($this->hasLocalFile($path)) {
			return $this->fetchLocalFile($path);
		}

		return $this->fetchDistFile($path);
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

	public function getDataSize(): int
	{
		return DB::getInstance()->getTableSize(sprintf('modules_data_%s', $this->name));
	}

	public function getCodeSize(): int
	{
		$dir = $this->dir();

		if ($dir) {
			return $dir->getRecursiveSize();
		}

		return 0;
	}

	public function getFilesSize(): int
	{
		$dir = $this->storage();

		if ($dir) {
			return $dir->getRecursiveSize();
		}

		return 0;
	}

	public function canDelete(): bool
	{
		return $this->hasLocal() && !$this->hasDist();
	}

	public function canReset(): bool
	{
		return $this->hasLocal() && $this->hasDist();
	}

	public function canDeleteData(): bool
	{
		return !empty($this->config) || $this->hasData();
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

			$out[$file->name] = (object) [
				'name'      => $file->name,
				'dir'       => $file->isDir(),
				'path'      => $_path,
				'file_path' => $file->path,
				'type'      => $file->mime,
				'local'     => true,
				'dist'      => false,
				'file'      => $file,
			];
		}

		$dist_path = $this->distPath(trim($path, '/'));

		if (is_dir($dist_path)) {
			foreach (scandir($dist_path) as $file) {
				if (substr($file, 0, 1) == '.') {
					continue;
				}

				if (isset($out[$file])) {
					$out[$file]->dist = true;
					continue;
				}

				$out[$file] = (object) [
					'name'      => $file,
					'type'      => mime_content_type($dist_path . '/' . $file),
					'dir'       => is_dir($dist_path . '/' . $file),
					'path'      => $path . $file,
					'local'     => false,
					'dist'      => true,
					'file_path' => $base . $path . '/' . $file,
					'file'      => null,
					'dist_path' => $dist_path . '/' . $file,
				];
			}
		}

		foreach ($out as &$file) {
			$file->editable = !$file->dir && (UserTemplate::isTemplate($file->path)
				|| substr($file->type, 0, 5) === 'text/'
				|| preg_match('/\.(?:json|md|skriv|html|css|js|ini)$/', $file->name));
			$file->open_url = '!common/files/preview.php?p=' . rawurlencode($file->file_path);
			$file->edit_url = '!common/files/edit.php?fallback=code&p=' . rawurlencode($file->file_path);
			$file->delete_url = '!common/files/delete.php?p=' . rawurlencode($file->file_path);
		}

		unset($file);

		uasort($out, function ($a, $b) {
			if ($a->dir == $b->dir) {
				return strnatcasecmp($a->name, $b->name);
			}
			elseif ($a->dir && !$b->dir) {
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
		$this->resetChanges();
		$this->deleteData();

		return parent::delete();
	}

	public function resetChanges(): void
	{
		$dir = $this->dir();

		if ($dir) {
			$dir->delete();
		}
	}

	public function deleteData(): void
	{
		DB::getInstance()->exec(sprintf('DROP TABLE IF EXISTS modules_data_%s; UPDATE modules SET config = NULL WHERE name = \'%1$s\';', $this->name));

		// Delete all files
		if ($dir = Files::get($this->storage_root())) {
			$dir->delete();
		}
	}

	public function url(string $file = '', array $params = null)
	{
		if (null !== $params) {
			$params = '?' . http_build_query($params);
		}

		if ($this->web && $this->enabled && !$file) {
			return BASE_URL;
		}

		return sprintf('%sm/%s/%s%s', BASE_URL, $this->name, $file, $params);
	}

	public function public_url(string $file = '', array $params = null)
	{
		return str_replace(BASE_URL, WWW_URL, $this->url($file, $params));
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
		$ut->setModule($this);

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
		if (substr(Utils::basename($path), 0, 1) === '.') {
			throw new UserException('Unknown path', 404);
		}

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

			return;
		}
		// Serve a static file from a user module
		elseif ($has_local_file) {
			$file = Files::get(File::CONTEXT_MODULES . '/' . $this->name . '/' . $path);

			if (!$file) {
				throw new UserException('Invalid path');
			}

			$file->validateCanRead();
			$file->serve();
		}
		// Serve a static file from dist path
		else {
			$type = $this->getFileTypeFromExtension($path);
			$real_path = $this->distPath($path);

			if (!is_file($real_path)) {
				throw new UserException('Invalid path', 404);
			}

			if ($this->web) {
				// Create symlink to static file
				Cache::link($path, $real_path);
			}

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
		$module = $this;

		$signal = Plugins::fire('web.request.before', true, compact('path', 'uri', 'module'));

		if ($signal && $signal->isStopped()) {
			return;
		}

		unset($signal);

		$type = null;

		$ut = $this->template($path);
		$ut->assignArray($params);
		$content = $ut->fetch();
		$type = $ut->getContentType();
		$code = $ut->getStatusCode();

		if ($uri !== null && preg_match('!html|xml|text!', $type) && !$ut->get('nocache') && $code == 200) {
			$cache = true;
		}
		else {
			$cache = false;
		}

		// Call plugins, allowing them to modify the content
		$signal = Plugins::fire(
			'web.request',
			true,
			compact('path', 'uri', 'module', 'content', 'type', 'cache', 'code'),
			compact('type', 'cache', 'content', 'code')
		);

		if ($signal && $signal->isStopped()) {
			return;
		}

		if ($signal) {
			$ut->setHeader('type', $signal->getOut('type'));
			$ut->setHeader('code', $signal->getOut('code'));
			$cache = $signal->getOut('cache');
			$content = $signal->getOut('content');
		}

		unset($signal);

		$ut->dumpHeaders();

		if ($type == 'application/pdf') {
			Utils::streamPDF($content);
		}
		else {
			echo $content;
		}

		if ($cache) {
			Cache::store($uri, $content);
		}

		Plugins::fire('web.request.after', false, compact('path', 'uri', 'module', 'content', 'type', 'cache'));
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

	public function export(Session $session): void
	{
		$download_name = 'module_' . $this->name;

		header('Content-type: application/zip');
		header(sprintf('Content-Disposition: attachment; filename="%s"', $download_name. '.zip'));

		$target = 'php://output';
		$zip = new ZipWriter($target);
		$zip->setCompression(9);

		$add = function ($path) use ($zip, &$add) {
			foreach ($this->listFiles($path) as $file) {
				if ($file->dir) {
					$add($file->path);
				}
				elseif ($file->local) {
					if ($pointer = $file->file->getReadOnlyPointer()) {
						$zip->addFromPointer($file->file_path, $pointer);
					}
					elseif ($path = $file->file->getLocalFilePath()) {
						$zip->addFromPath($file->file_path, $path);
					}
				}
				else {
					$zip->addFromPath($file->file_path, $file->dist_path);
				}
			}
		};

		$add(null);

		$zip->close();
	}

	public function save(bool $selfcheck = true): bool
	{
		$enabled_web = isset($this->_modified['enabled']) && $this->enabled && $this->web;
		$r = parent::save($selfcheck);

		if ($r && $enabled_web) {
			DB::getInstance()->preparedQuery('UPDATE modules SET enabled = 0 WHERE web = 1 AND enabled = 1 AND name != ?;', $this->name);
		}

		return $r;
	}

	public function listSnippets(): array
	{
		$out = [];

		foreach (DB::getInstance()->iterate('SELECT name FROM modules_templates WHERE id_module = ? AND name LIKE \'snippets/%\';', $this->id()) as $row) {
			$out[$row->name] = self::SNIPPETS[$row->name];
		}

		return $out;
	}
}
