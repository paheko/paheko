<?php

namespace Paheko\Entities;

use Paheko\Entity;
use Paheko\DB;
use Paheko\Plugins;
use Paheko\Template;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\ValidationException;
use Paheko\Files\Files;
use Paheko\UserTemplate\UserTemplate;
use Paheko\Users\Session;
use Paheko\Web\Router;

use Paheko\Entities\Files\File;
use Paheko\Entities\Users\Category;

use const Paheko\{PLUGINS_ROOT, WWW_URL, ROOT, ADMIN_URL};

class Plugin extends Entity
{
	const META_FILE = 'plugin.ini';
	const CONFIG_FILE = 'admin/config.php';
	const INDEX_FILE = 'admin/index.php';
	const ICON_FILE = 'admin/icon.svg';
	const INSTALL_FILE = 'install.php';
	const UPGRADE_FILE = 'upgrade.php';
	const UNINSTALL_FILE = 'uninstall.php';

	const PROTECTED_FILES = [
		self::META_FILE,
		self::INSTALL_FILE,
		self::UPGRADE_FILE,
		self::UNINSTALL_FILE,
	];

	const TABLE = 'plugins';

	protected ?int $id;

	/**
	 * Directory name
	 */
	protected string $name;

	protected string $label;
	protected string $version;

	protected ?string $description;
	protected ?string $author;
	protected ?string $author_url;

	protected bool $home_button;
	protected bool $menu;
	protected ?string $restrict_section;
	protected ?int $restrict_level;

	protected ?\stdClass $config = null;
	protected bool $enabled;

	protected ?string $_broken_message = null;

	protected ?\stdClass $_ini;

	public function hasCode(): bool
	{
		return Plugins::exists($this->name);
	}

	public function selfCheck(): void
	{
		$this->assert(preg_match('/^' . Plugins::NAME_REGEXP . '$/', $this->name), 'Nom unique d\'extension invalide: ' . $this->name);
		$this->assert(isset($this->label) && trim($this->label) !== '', sprintf('%s : le nom de l\'extension ("name") ne peut rester vide', $this->name));
		$this->assert(isset($this->label) && trim($this->version) !== '', sprintf('%s : la version ne peut rester vide', $this->name));

		if ($this->hasCode() || $this->enabled) {
			$this->assert($this->hasFile(self::META_FILE), 'Le fichier plugin.ini est absent');
			$this->assert(!$this->menu || $this->hasFile(self::INDEX_FILE), 'Le fichier admin/index.php n\'existe pas alors que la directive "menu" est activée.');
			$this->assert(!$this->home_button || $this->hasFile(self::INDEX_FILE), 'Le fichier admin/index.php n\'existe pas alors que la directive "home_button" est activée.');
			$this->assert(!$this->home_button || $this->hasFile(self::ICON_FILE), 'Le fichier admin/icon.svg n\'existe pas alors que la directive "home_button" est activée.');
		}

		$this->assert(!isset($this->restrict_section) || in_array($this->restrict_section, Session::SECTIONS, true), 'Restriction de section invalide');
		$this->assert(!isset($this->restrict_level) || in_array($this->restrict_level, Session::ACCESS_LEVELS, true), 'Restriction de niveau invalide');

		if (isset($this->restrict_section, $this->restrict_level)) {
			$this->assert(array_key_exists($this->restrict_level, Category::PERMISSIONS[$this->restrict_section]['options']),
				sprintf('This restricted access level doesn\'t exist for this section: %s', $this->restrict_level));
		}

		$this->assert(Plugins::isAllowed($this->name), 'Cette extension est désactivée par l\'hébergeur');
	}

	public function setBrokenMessage(string $str)
	{
		$this->_broken_message = $str;
	}

	public function getBrokenMessage(): ?string
	{
		return $this->_broken_message;
	}

	public function getINIProperties(): ?\stdClass
	{
		if (isset($this->_ini)) {
			return $this->_ini;
		}

		if (!$this->hasFile(self::META_FILE)) {
			return null;
		}

		try {
			$ini = Utils::parse_ini_file($this->path(self::META_FILE), false);
		}
		catch (\RuntimeException $e) {
			throw new ValidationException(sprintf('Le fichier plugin.ini est invalide pour "%s" : %s', $this->name, $e->getMessage()), 0, $e);
		}

		if (empty($ini)) {
			return null;
		}

		$ini = (object) $ini;

		if (!isset($ini->name)) {
			return null;
		}

		$this->_ini = $ini;

		return $ini;
	}

	/**
	 * Fills information from plugin.ini file
	 */
	public function updateFromINI(): bool
	{
		$ini = $this->getINIProperties();

		if (!$ini) {
			return false;
		}

		if (!empty($ini->min_version)) {
			$this->assert(version_compare(\Paheko\paheko_version(), $ini->min_version, '>='), sprintf('L\'extension "%s" nécessite Paheko version %s ou supérieure.', $this->name, $ini->min_version));
		}

		$restrict_section = null;
		$restrict_level = null;

		if (isset($ini->restrict_section, $ini->restrict_level)) {
			$restrict_section = $ini->restrict_section;
			$restrict_level = Session::ACCESS_LEVELS[$ini->restrict_level] ?? null;
		}

		$this->set('label', $ini->name);
		$this->set('version', $ini->version);
		$this->set('description', $ini->description ?? null);
		$this->set('author', $ini->author ?? null);
		$this->set('author_url', $ini->author_url ?? null);
		$this->set('home_button', !empty($ini->home_button));
		$this->set('menu', !empty($ini->menu));
		$this->set('restrict_section', $restrict_section);
		$this->set('restrict_level', $restrict_level);

		return true;
	}

	public function icon_url(): ?string
	{
		if (!$this->hasFile(self::ICON_FILE)) {
			return null;
		}

		return $this->url(self::ICON_FILE);
	}

	public function path(string $file = null): ?string
	{
		$path = Plugins::getPath($this->name);

		if (!$path) {
			return null;
		}

		return $path . ($file ? '/' . $file : '');
	}

	public function hasFile(string $file): bool
	{
		$path = $this->path($file);

		if (!$path) {
			return false;
		}

		return file_exists($path);
	}

	public function hasConfig(): bool
	{
		return $this->hasFile(self::CONFIG_FILE);
	}

	public function fetchFile(string $path): ?string
	{
		if (!$this->hasFile($path)) {
			return null;
		}

		return file_get_contents($this->path($path));
	}

	public function url(string $file = '', array $params = null)
	{
		if (null !== $params) {
			$params = '?' . http_build_query($params);
		}

		if (substr($file, 0, 6) == 'admin/') {
			$url = ADMIN_URL;
			$file = substr($file, 6);
		}
		else {
			$url = WWW_URL;
		}

		return sprintf('%sp/%s/%s%s', $url, $this->name, $file, $params);
	}

	public function storage_root(): string
	{
		return File::CONTEXT_EXTENSIONS . '/p/' . $this->name;
	}

	public function getConfig(string $key = null)
	{
		if (is_null($key)) {
			return $this->config;
		}

		if ($this->config && property_exists($this->config, $key)) {
			return $this->config->$key;
		}

		return null;
	}

	public function setConfigProperty(string $key, $value = null)
	{
		if (null === $this->config) {
			$this->config = new \stdClass;
		}

		if (is_null($value)) {
			unset($this->config->$key);
		}
		else {
			$this->config->$key = $value;
		}

		$this->_modified['config'] = true;
	}

	public function setConfig(\stdClass $config)
	{
		$this->config = $config;
		$this->_modified['config'] = true;
	}

	/**
	 * Associer un signal à un callback du plugin
	 * @param  string $signal   Nom du signal (par exemple boucle.agenda pour la boucle de type AGENDA)
	 * @param  mixed  $callback Callback, sous forme d'un nom de fonction ou de méthode statique
	 * @return boolean TRUE
	 */
	public function registerSignal(string $signal, callable $callback): void
	{
		$callable_name = '';

		if (!is_callable($callback, true, $callable_name) || !is_string($callable_name))
		{
			throw new \LogicException('Le callback donné n\'est pas valide.');
		}

		// pour empêcher d'appeler des méthodes de Paheko après un import de base de données "hackée"
		if (strpos($callable_name, 'Paheko\\Plugin\\') !== 0)
		{
			throw new \LogicException('Le callback donné n\'utilise pas le namespace Paheko\\Plugin : ' . $callable_name);
		}

		$db = DB::getInstance();

		$callable_name = str_replace('Paheko\\Plugin\\', '', $callable_name);

		$db->preparedQuery('INSERT OR REPLACE INTO plugins_signals VALUES (?, ?, ?);', [$signal, $this->name, $callable_name]);
	}

	public function unregisterSignal(string $signal): void
	{
		DB::getInstance()->preparedQuery('DELETE FROM plugins_signals WHERE plugin = ? AND signal = ?;', [$this->name, $signal]);
	}

	public function delete(): bool
	{
		if ($this->hasFile(self::UNINSTALL_FILE)) {
			$this->call(self::UNINSTALL_FILE, true);
		}

		// Delete all files
		if ($dir = Files::get($this->storage_root())) {
			$dir->delete();
		}

		$db = DB::getInstance();
		$db->delete('plugins_signals', 'plugin = ?', $this->name);
		return parent::delete();
	}

	/**
	 * Renvoie TRUE si le plugin a besoin d'être mis à jour
	 * (si la version notée dans la DB est différente de la version notée dans paheko_plugin.ini)
	 * @return boolean TRUE si le plugin doit être mis à jour, FALSE sinon
	 */
	public function needUpgrade(): bool
	{
		try {
			$infos = (object) Utils::parse_ini_file($this->path(self::META_FILE), false);
		}
		catch (\RuntimeException $e) {
			return false;
		}

		if (version_compare($this->version, $infos->version, '!=')) {
			return true;
		}

		return false;
	}

	/**
	 * Mettre à jour le plugin
	 * Appelle le fichier upgrade.php dans l'archive si celui-ci existe.
	 */
	public function upgrade(): void
	{
		$this->updateFromINI();

		if ($this->hasFile(self::UPGRADE_FILE)) {
			$this->call(self::UPGRADE_FILE, true);
		}

		$this->save();
	}

	public function upgradeIfRequired(): void
	{
		if ($this->needUpgrade()) {
			$this->upgrade();
		}
	}

	public function oldVersion(): ?string
	{
		return $this->getModifiedProperty('version');
	}

	public function call(string $file, bool $allow_protected = false): void
	{
		$file = ltrim($file, './');

		if (preg_match('!(?:\.\.|[/\\\\]\.|\.[/\\\\])!', $file)) {
			throw new UserException('Chemin de fichier incorrect.');
		}

		if (!$allow_protected && in_array($file, self::PROTECTED_FILES)) {
			throw new UserException('Le fichier ' . $file . ' ne peut être appelé par cette méthode.');
		}

		$path = $this->path($file);

		if (!file_exists($path)) {
			throw new UserException(sprintf('Le fichier "%s" n\'existe pas dans le plugin "%s"', $file, $this->name));
		}

		if (is_dir($path)) {
			throw new UserException(sprintf('Sécurité : impossible de lister le répertoire "%s" du plugin "%s".', $file, $this->name));
		}

		$is_private = (0 === strpos($file, 'admin/'));

		// Créer l'environnement d'exécution du plugin
		if (substr($file, -4) === '.php') {
			if (substr($file, 0, 6) == 'admin/' || substr($file, 0, 7) == 'public/') {
				define('Paheko\PLUGIN_ROOT', $this->path());
				define('Paheko\PLUGIN_URL', WWW_URL . 'p/' . $this->name . '/');
				define('Paheko\PLUGIN_ADMIN_URL', ADMIN_URL .'p/' . $this->name . '/');
				define('Paheko\PLUGIN_QSP', '?');

				$tpl = Template::getInstance();

				if ($is_private) {
					require ROOT . '/www/admin/_inc.php';
					$tpl->assign('current', 'plugin_' . $this->name);
				}

				$tpl->assign('plugin', $this);
				$tpl->assign('plugin_url', \Paheko\PLUGIN_URL);
				$tpl->assign('plugin_admin_url', \Paheko\PLUGIN_ADMIN_URL);
				$tpl->assign('plugin_root', \Paheko\PLUGIN_ROOT);
			}

			$plugin = $this;

			include $path;
		}
		else {
			Plugins::routeStatic($this->name, $file);
		}
	}

	public function route(string $uri): void
	{
		$uri = ltrim($uri, '/');
		$session = Session::getInstance();

		if (0 === strpos($uri, 'admin/')) {
			if (!$session->isLogged()) {
				Utils::redirect('!login.php');
			}

			if ($uri === 'admin/config.php') {
				$session->requireAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN);
			}

			// Restrict access
			if (isset($this->restrict_section, $this->restrict_level)) {
				$session->requireAccess($this->restrict_section, $this->restrict_level);
			}
		}

		if (!$uri || substr($uri, -1) == '/') {
			$uri .= 'index.php';
		}

		try {
			$this->call($uri);
		}
		catch (UserException $e) {
			http_response_code(404);
			throw new UserException($e->getMessage());
		}
	}

	public function isAvailable(): bool
	{
		return $this->hasFile(self::META_FILE);
	}

	public function assertCanBeEnabled(): void
	{
		if (!Plugins::isAllowed($this->name)) {
			throw new \RuntimeException('This plugin is not allowed: ' . $this->name);
		}

		if (!$this->hasFile(self::META_FILE)) {
			throw new UserException(sprintf('Le plugin "%s" n\'est pas une extension Paheko : fichier plugin.ini manquant.', $this->name));
		}

		$this->updateFromINI();
	}

	public function enable(): void
	{
		$this->assertCanBeEnabled();

		$db = DB::getInstance();
		$db->begin();
		$exists = $this->exists();

		$this->set('enabled', true);
		$this->save();

		if (!$exists && $this->hasFile(self::INSTALL_FILE)) {
			$this->call(self::INSTALL_FILE, true);
		}

		$db->commit();
	}

	public function disable(): void
	{
		$this->set('enabled', false);
		$this->save();
	}
}
