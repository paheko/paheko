<?php

namespace Paheko\Entities;

use Paheko\Entity;
use Paheko\DB;
use Paheko\Plugins;
use Paheko\UserException;
use Paheko\ValidationException;
use Paheko\Utils;
use Paheko\Files\Files;
use Paheko\UserTemplate\Modules;
use Paheko\UserTemplate\UserTemplate;
use Paheko\Users\Session;
use Paheko\Web\Cache;
use Paheko\Web\Router;

use KD2\ZipWriter;
use KD2\DB\EntityManager as EM;

use Paheko\Entities\Files\File;
use Paheko\Entities\Users\Category;

use DateTime;
use stdClass;

use const Paheko\{ROOT, WWW_URL, BASE_URL, PLUGINS_BLOCKLIST};
use function Paheko\paheko_version;

class Module extends Entity
{
	const ROOT = File::CONTEXT_MODULES;
	const DIST_ROOT = ROOT . '/modules';
	const META_FILE = 'module.ini';
	const ICON_FILE = 'icon.svg';
	const CONFIG_FILE = 'config.html';
	const INDEX_FILE = 'index.html';
	const MIGRATION_FILE = 'migration.tpl';

	// Snippets, don't forget to create alias constant in UserTemplate\Modules class
	const SNIPPET_TRANSACTION = 'snippets/transaction_details.html';
	const SNIPPET_USER = 'snippets/user_details.html';
	const SNIPPET_HOME_BUTTON = 'snippets/home_button.html';
	const SNIPPET_MY_SERVICES = 'snippets/my_services.html';
	const SNIPPET_MY_DETAILS = 'snippets/my_details.html';
	const SNIPPET_BEFORE_NEW_TRANSACTION = 'snippets/transaction_new.html';
	const SNIPPET_MARKDOWN_EXTENSION = 'snippets/markdown/%s.html';

	const SNIPPETS = [
		self::SNIPPET_HOME_BUTTON => 'icône sur la page d\'accueil',
		self::SNIPPET_USER => 'en bas de la fiche d\'un membre',
		self::SNIPPET_TRANSACTION => 'en bas de la fiche d\'une écriture',
		self::SNIPPET_MY_SERVICES => 'sur la page "Mes activités"',
		self::SNIPPET_MY_DETAILS => 'sur la page "Mes infos personnelles"',
		self::SNIPPET_BEFORE_NEW_TRANSACTION => 'avant le formulaire de saisie d\'écriture',
	];

	const VALID_NAME_REGEXP = '/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/';
	const TABLE_PREFIX = 'module_';
	const DOCUMENTS_TABLE_NAME = 'documents';
	const TABLE_NAME_REGEXP = '/^[a-z]+(?:_[a-z]+)*$/';

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

	protected ?string $version;
	protected ?string $db_version;

	/**
	 * System modules are always available, disabling them only hides the links
	 */
	protected bool $system;

	protected ?DateTime $last_updated = null;

	protected bool $_has_data;

	protected ?\stdClass $_ini;

	protected ?string $_broken_message = null;

	public function refreshIfNeeded(): void
	{
		// We only need to refresh modules from the local filesystem
		// as they're the ones that might have their code updated
		// but the DB would be out of sync
		if (!$this->hasDist()) {
			return;
		}

		$ini_updated = filemtime($this->distPath(self::META_FILE));

		if ($this->last_updated
			&& $ini_updated <= $this->last_updated->getTimestamp()) {
			return;
		}

		$this->refresh(true);
	}

	public function refresh(bool $suppress_errors = false): void
	{
		if ($this->isModified()) {
			throw new \LogicException('Cannot refresh a modified module');
		}

		try {
			$this->updateFromINI();
			$this->save();
			$this->updateTemplates();
		}
		catch (ValidationException $e) {
			// Ignore self-check errors here, or it might block module actions
			if (!$suppress_errors) {
				throw $e;
			}
		}
	}

	public function assertIsValid(): void
	{
		$this->assert($this->isValid(), 'Nom unique de module invalide: ' . $this->name);
	}

	public function isValid(): bool
	{
		return (bool) preg_match(self::VALID_NAME_REGEXP, $this->name);
	}

	public function selfCheck(): void
	{
		$this->assertIsValid();
		$this->assert(trim($this->label) !== '', 'Le libellé ne peut rester vide');
		$this->assert(!isset($this->author_url) || preg_match('!^(?:https?://|mailto:)!', $this->author_url), 'L\'adresse du site de l\'auteur est invalide');

		$this->assert(!isset($this->restrict_section) || in_array($this->restrict_section, Session::SECTIONS, true), 'Restriction de section invalide');

		if (isset($this->restrict_section)) {
			$this->assert(isset($this->restrict_level) && in_array($this->restrict_level, Session::ACCESS_LEVELS, true), 'Restriction de niveau invalide');
			$this->assert(array_key_exists($this->restrict_level, Category::PERMISSIONS[$this->restrict_section]['options']),
				'This restricted access level doesn\'t exist for this section');
		}

		if (!$this->exists()) {
			$this->assert(!DB::getInstance()->test(self::TABLE, 'name = ?', $this->name), 'Un module existe déjà avec ce nom unique');
			$this->assert(!DB::getInstance()->test(Plugin::TABLE, 'name = ?', $this->name), 'Un plugin existe déjà avec ce nom unique');
		}
	}

	public function selfCheckUser(): void
	{
		$this->assert(!Modules::distExists($this->name), 'Un module existe déjà avec ce nom unique');
		$this->assert(!Plugins::exists($this->name), 'Un plugin existe déjà avec ce nom unique');
		$this->assert(!in_array($this->name, PLUGINS_BLOCKLIST ?? [], true), 'Ce nom unique de module ne peut être utilisé, merci d\'en choisir un autre');
	}

	public function importForm(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['restrict'])) {
			$this->set('restrict_section', strtok($source['restrict'], '_') ?: null);
			$this->set('restrict_level', (int)strtok('') ?: null);
		}

		parent::importForm($source);
	}

	public function getBrokenMessage(): ?string
	{
		$this->checkIfBroken();
		return $this->_broken_message;
	}

	public function checkIfBroken(): void
	{
		if ($this->_broken_message !== null) {
			return;
		}

		try {
			$this->selfCheck();
		}
		catch (ValidationException $e) {
			$this->_broken_message = $e->getMessage();
			return;
		}

		$this->_broken_message = '';
	}

	public function isBroken(): bool
	{
		$this->checkIfBroken();
		return $this->_broken_message !== '';
	}

	public function getINIProperties(bool $use_local = true): stdClass
	{
		if (isset($this->_ini) && $use_local) {
			return $this->_ini;
		}

		if ($use_local && ($file = Files::get($this->path(self::META_FILE)))) {
			$ini = $file->fetch();
			$from_dist = false;
		}
		elseif (file_exists($this->distPath(self::META_FILE))) {
			$ini = file_get_contents($this->distPath(self::META_FILE));
			$from_dist = true;
		}
		else {
			throw new ValidationException('Le fichier module.ini est absent');
		}

		try {
			$ini = Utils::parse_ini_string($ini, false);
		}
		catch (\RuntimeException $e) {
			throw new ValidationException(sprintf('Le fichier module.ini est invalide : %s', $e->getMessage()));
		}

		if (empty($ini)) {
			throw new ValidationException('Le fichier module.ini est vide');
		}

		$ini = (object) $ini;

		if (!isset($ini->name)) {
			throw new ValidationException('Le fichier module.ini est invalide : la clé "name" n\'existe pas');
		}

		if (isset($ini->min_version)
			&& version_compare($ini->min_version, paheko_version(), '<')) {
			throw new ValidationException(sprintf('Ce module nécessite Paheko %s ou supérieur', $ini->min_version));
		}

		// Don't allow user code to set itself as a system module
		if (!$from_dist) {
			unset($ini->system);
		}

		if ($use_local) {
			$this->_ini = $ini;
		}

		$ini->allow_user_restrict ??= true;

		return $ini;
	}

	/**
	 * Fills information from module.ini file
	 */
	public function updateFromINI(bool $use_local = true): bool
	{
		$ini = $this->getINIProperties($use_local);

		if (!$ini) {
			return false;
		}

		$restrict_section = null;
		$restrict_level = null;

		if (isset($ini->restrict_section, $ini->restrict_level)) {
			$restrict_section = $ini->restrict_section;
			$restrict_level = Session::ACCESS_LEVELS[$ini->restrict_level] ?? null;
		}

		$this->set('version', $ini->version ?? null);
		$this->set('label', $ini->name);
		$this->set('description', $ini->description ?? null);
		$this->set('author', $ini->author ?? null);
		$this->set('author_url', $ini->author_url ?? null);
		$this->set('web', !empty($ini->web));
		$this->set('home_button', !empty($ini->home_button));
		$this->set('menu', !empty($ini->menu));

		$this->set('restrict_section', $restrict_section);
		$this->set('restrict_level', $restrict_level);

		if (!empty($ini->system)) {
			$this->set('system', true);
		}

		$this->set('last_updated', new DateTime);

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

	public function upgradeIfRequired(): void
	{
		if (!$this->version
			|| $this->version === $this->db_version
			|| version_compare((string) $this->version, (string) $this->db_version, '==')) {
			return;
		}

		if (!$this->hasFile(self::MIGRATION_FILE)) {
			return;
		}

		// Execute migration
		$db = DB::getInstance();

		// We need to disable foreign keys so that everything works
		// when we re-create a table from scratch
		// legacy_alter_table MUST remain OFF
		// We CANNOT change foreign_keys *INSIDE* a transaction so we need to have
		$db->exec('PRAGMA foreign_keys = 0;');
		$db->begin();
		$r = $this->fetch(self::MIGRATION_FILE, []);
		$db->commit();
		$db->exec('PRAGMA foreign_keys = 1;');

		$r = trim($r);

		// If the template returned something display it and stop there
		if ($r !== '') {
			if (!str_starts_with($r, '<!DOCTYPE')
				&& !str_starts_with($r, '<')) {
				echo '<!DOCTYPE html><meta charset="utf-8" />';
			}

			echo $r;

			exit;
		}

		$this->set('db_version', $this->version);
		$this->save();
	}

	public function updateTemplates(): void
	{
		$check = self::SNIPPETS + [self::CONFIG_FILE => 'Config'];
		$db = DB::getInstance();

		$db->begin();
		$db->delete('modules_templates', 'id_module = ' . (int)$this->id());

		foreach ($check as $file => $label) {
			if (Files::exists($this->path($file)) || file_exists($this->distPath($file))) {
				$db->insert('modules_templates', ['id_module' => $this->id(), 'name' => $file]);
			}
		}

		foreach ($this->listFiles('snippets/markdown') as $file) {
			$db->insert('modules_templates', ['id_module' => $this->id(), 'name' => $file->path]);
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

	public function path(?string $file = null): string
	{
		return self::ROOT . '/' . $this->name . ($file ? '/' . $file : '');
	}

	public function distPath(?string $file = null): string
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

	public function hasFile(?string $file): bool
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

	public function hasLocalDir(string $path): bool
	{
		return Files::getType($this->path($path)) === File::TYPE_DIRECTORY;
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

	public function getLocalFile(string $path): ?File
	{
		return Files::get($this->path($path));
	}

	public function fetchLocalFile(string $path): ?string
	{
		$file = $this->getLocalFile($path);
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

	public function getTableNamePrefix(): string
	{
		return self::TABLE_PREFIX . $this->name . '_';
	}

	public function getRealTableName(string $name): string
	{
		return Modules::getModuleTableName($this->name, $name);
	}

	public function hasDocumentsTable(): bool
	{
		return DB::getInstance()->test('sqlite_master',
			'type = \'table\' AND name = ?',
			$this->getDocumentsTableName()
		);
	}

	public function getDocumentsTableName(): string
	{
		return $this->getTableNamePrefix() . self::DOCUMENTS_TABLE_NAME;
	}

	public function hasData(): bool
	{
		$db = DB::getInstance();

		$this->_has_data ??= $db->test('sqlite_master',
			'type = \'table\' AND name LIKE ?',
			$this->getTableNamePrefix() . '%',
		);

		return $this->_has_data;
	}

	public function getDataSize(): int
	{
		$total = 0;
		$db = DB::getInstance();

		foreach ($this->getTablesNames() as $real_name) {
			$total += (int) $db->getTableSize($real_name);
		}

		return $total;
	}

	public function getConfigSize(): int
	{
		return (int) DB::getInstance()->firstColumn('SELECT LENGTH(config) FROM modules WHERE id = ?;', $this->id());
	}

	public function getCodeSize(): int
	{
		$dir = $this->dir();

		if ($dir) {
			return $dir->getRecursiveSize();
		}

		return 0;
	}

	public function getCodeSLOC(?string $path = null): int
	{
		$count = 0;

		foreach ($this->listFiles($path) as $file) {
			if ($file->dir) {
				$count += $this->getCodeSLOC($file->path);
				continue;
			}
			elseif (!$file->editable) {
				continue;
			}

			if (!empty($file->dist_path)) {
				$fp = fopen($file->dist_path, 'r');
			}
			else {
				$file = Files::get($file->file_path);
				$fp = $file->getReadOnlyPointer();
			}

			if (!$fp) {
				continue;
			}

			$in_comment = false;

			while (!feof($fp)) {
				$line = trim(fgets($fp));

				// Skip blank lines
				if ($line === '') {
					continue;
				}

				if (false !== strpos($line, '{{*') || false !== strpos($line, '/*')) {
					$in_comment = true;
				}

				if (false !== strpos($line, '*}}') || false !== strpos($line, '*/')) {
					$in_comment = false;
				}

				// Skip comments
				if ($in_comment || substr($line, 0, 2) === '//') {
					continue;
				}

				$count++;
			}

			fclose($fp);
		}

		return $count;
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
		return !$this->enabled && $this->hasLocal() && !$this->hasDist();
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

		$path ??= '';
		$local_path = trim($base . '/' . $path, '/');

		foreach (Files::list($local_path) as $file) {
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

		$dist_path = $this->distPath($path);

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
					'path'      => trim($path . '/' . $file, '/'),
					'local'     => false,
					'dist'      => true,
					'file_path' => $base . '/' . trim($path . '/' . $file, '/'),
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
		$db = DB::getInstance();

		$tables = array_values($this->getTablesNames());

		$db->begin();

		// Truncate tables before delete, this will trigger all foreign key actions (CASCADE/SET NULL)
		foreach ($tables as $name) {
			$db->exec(sprintf('DELETE FROM %s;', $db->quoteIdentifier($name)));
		}

		$db->commit();

		// Disable foreign keys for deleting tables
		$db->beginSchemaUpdate();

		// Delete config, reset db_version
		$db->preparedQuery('UPDATE modules SET config = NULL, db_version = NULL WHERE id = ?;', $this->id());

		// Delete tables config
		$db->preparedQuery('DELETE FROM modules_tables WHERE id_module = ?;', $this->id());

		// Delete all tables
		foreach ($tables as $name) {
			$db->exec(sprintf('DROP TABLE IF EXISTS %s;', $db->quoteIdentifier($name)));
		}

		$db->commitSchemaUpdate();

		// Delete all files
		if ($dir = Files::get($this->storage_root())) {
			$dir->delete();
		}
	}

	public function url(string $file = '', ?array $params = null)
	{
		if (null !== $params) {
			$params = '?' . http_build_query($params);
		}

		if ($this->web && $this->enabled && !$file) {
			return BASE_URL;
		}

		return sprintf('%sm/%s/%s%s', BASE_URL, $this->name, $file, $params);
	}

	public function public_url(string $file = '', ?array $params = null)
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
		if ($file === self::CONFIG_FILE) {
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
			// Only upgrade for templates, not static files
			$this->upgradeIfRequired();

			// Error if path is not valid
			// we allow any path for static files, but not for skeletons
			if (!$this->isValidPath($path)) {
				if ($this->web) {
					$path = '404.html';
				}
				else {
					throw new UserException('This address is invalid.', 404);
				}
			}

			if ($this->web) {
				$this->serveWeb($path, $params);
				return;
			}
			else {
				$ut = $this->template($path);
				$ut->assignArray($params);
				$ut->serve();
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
		$module = $this;

		$signal = Plugins::fire('web.request.before', true, compact('path', 'uri', 'module'));

		if ($signal && $signal->isStopped()) {
			return;
		}

		unset($signal);

		try {
			$ut = $this->template($path);
		}
		catch (\InvalidArgumentException $e) {
			try {
				// In case template path does not exist, or is a directory,
				// we expect 404.html to exist
				$ut = $this->template('404.html');
			}
			catch (\InvalidArgumentException $e) {
				// Fallback if 404.html does not exist
				throw new UserException('Page non trouvée. De plus, le squelette "404.html" n\'existe pas.', 404);
			}
		}

		$ut->assignArray($params);
		$content = $ut->fetchAndCatchErrors();
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

		if ($type === 'application/pdf') {
			Utils::streamPDF($content);
		}
		else {
			// For bots
			if ($type === 'text/html') {
				$h_url = Router::getHoneypotURL();
				$link = sprintf('<a href="%s" rel="nofollow noindex" aria-hidden="true" style="display: none; width: 0; height: 0; overflow: hidden;">En savoir plus sur nous</a>', $h_url);
				$content = preg_replace('/<body.*?>/i', '$0' . $link, $content);
			}

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

	public function export(): void
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
			$label = self::SNIPPETS[$row->name] ?? null;

			if (!$label && ($match = sscanf($row->name, 'snippets/markdown/%[^.].html'))) {
				$label = sprintf('extension MarkDown <<%s>>, utilisable dans les pages du site web', current($match));
			}

			$out[$row->name] = $label;
		}

		return $out;
	}

	public function enable(): void
	{
		$this->set('enabled', true);
		$this->save();
	}

	public function disable(): void
	{
		if ($this->web) {
			throw new \LogicException('Cannot disable web module');
		}

		$this->set('enabled', false);
		$this->save();
	}

	public function createTable(string $name, ?string $comment, array $columns): ModuleTable
	{
		$table = new ModuleTable;
		$table->set('id_module', $this->id());
		$table->setModule($this);
		$table->import(compact('name', 'comment'));
		$table->setColumns($columns);
		return $table;
	}

	public function getTable(string $name): ?ModuleTable
	{
		$table = EM::findOne(ModuleTable::class, 'SELECT * FROM @TABLE WHERE id_module = ? AND name = ?;', $this->id(), $name);

		if ($table) {
			$table->setModule($this);
		}

		return $table;
	}

	public function listTables(): array
	{
		return EM::getInstance(ModuleTable::class)->all('SELECT * FROM @TABLE WHERE id_module = ? ORDER BY name;', $this->id());
	}

	/**
	 * Return list of all module tables names, including the documents table
	 */
	public function getTablesNames(bool $with_documents = true): array
	{
		$db = DB::getInstance();
		$list = [];
		$prefix = $this->getTableNamePrefix();
		$documents_table_name = $this->getDocumentsTableName();
		$sql = 'SELECT name FROM sqlite_master WHERE type = \'table\' AND name LIKE ?;';

		foreach ($db->iterate($sql, $prefix . '%') as $row) {
			if (!$with_documents && $row->name === $documents_table_name) {
				continue;
			}

			$short_name = substr($row->name, strlen($prefix));
			$list[$short_name] = $row->name;
		}

		return $list;
	}

}
