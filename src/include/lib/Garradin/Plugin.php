<?php

namespace Garradin;

use Garradin\Users\Session;

class Plugin
{
	const PLUGIN_ID_REGEXP = '[a-z]+(?:_[a-z]+)*';

	protected $id = null;
	protected $plugin = null;
	protected $config_changed = false;

	/**
	 * Set to false to disable signal firing
	 * @var boolean
	 */
	static protected $signals = true;

	protected $mimes = [
		'css' => 'text/css',
		'gif' => 'image/gif',
		'htm' => 'text/html',
		'html' => 'text/html',
		'ico' => 'image/x-ico',
		'jpe' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'js' => 'application/javascript',
		'pdf' => 'application/pdf',
		'png' => 'image/png',
		'xml' => 'text/xml',
		'svg' => 'image/svg+xml',
	];

	static public function toggleSignals(bool $enabled) {
		self::$signals = $enabled;
	}

	static public function getPath($id)
	{
		if (file_exists(PLUGINS_ROOT . '/' . $id . '.tar.gz'))
		{
			return 'phar://' . PLUGINS_ROOT . '/' . $id . '.tar.gz';
		}
		elseif (is_dir(PLUGINS_ROOT . '/' . $id))
		{
			return PLUGINS_ROOT . '/' . $id;
		}

		return false;
	}

	static public function getURL(string $id, string $path = '')
	{
		return ADMIN_URL . 'p/' . $id . '/' . ltrim($path, '/');
	}

	static public function getPublicURL(string $id, string $path = '')
	{
		return WWW_URL . 'p/' . $id . '/' . ltrim($path, '/');
	}

	/**
	 * Construire un objet Plugin pour un plugin
	 * @param string $id Identifiant du plugin
	 * @throws UserException Si le plugin n'est pas installé (n'existe pas en DB)
	 */
	public function __construct(string $id)
	{
		$db = DB::getInstance();
		$this->plugin = $db->first('SELECT * FROM plugins WHERE id = ?;', $id);

		if (!$this->plugin)
		{
			throw new UserException(sprintf('Le plugin "%s" n\'existe pas ou n\'est pas installé correctement.', $id));
		}

		$this->plugin->config = json_decode($this->plugin->config ?? '');

		if (!is_object($this->plugin->config))
		{
			$this->plugin->config = new \stdClass;
		}

		// Juste pour vérifier que le fichier source du plugin existe bien
		self::getPath($id);

		$this->id = $id;
	}

	/**
	 * Enregistrer les changements dans la config
	 */
	public function __destruct()
	{
		if ($this->config_changed)
		{
			$db = DB::getInstance();
			$db->update('plugins', 
				['config' => json_encode($this->plugin->config)],
				'id = \'' . $this->id . '\'');
		}
	}

	/**
	 * Renvoie le chemin absolu vers l'archive du plugin
	 * @return string Chemin PHAR vers l'archive
	 */
	public function path()
	{
		return self::getPath($this->id);
	}

	/**
	 * Renvoie une entrée de la configuration ou la configuration complète
	 * @param  string $key Clé à rechercher, ou NULL si on désire toutes les entrées de la
	 * @return mixed       L'entrée demandée (mixed), ou l'intégralité de la config (array),
	 * ou NULL si l'entrée demandée n'existe pas.
	 */
	public function getConfig($key = null)
	{
		if (is_null($key))
		{
			return $this->plugin->config;
		}

		if (property_exists($this->plugin->config, $key))
		{
			return $this->plugin->config->$key;
		}

		return null;
	}

	/**
	 * Enregistre une entrée dans la configuration du plugin
	 * @param string $key   Clé à modifier
	 * @param mixed  $value Valeur à enregistrer, choisir NULL pour effacer cette clé de la configuration
	 * @return boolean 		TRUE si tout se passe bien
	 */
	public function setConfig($key, $value = null)
	{
		if (is_null($value))
		{
			unset($this->plugin->config->$key);
		}
		else
		{
			$this->plugin->config->$key = $value;
		}

		$this->config_changed = true;

		return true;
	}

	/**
	 * Remplace toute la config du plugin
	 * @param \stdClass $config Configuration complète du plugin
	 */
	public function setConfigAll(\stdClass $config)
	{
		$this->plugin->config = $config;
		$this->config_changed = true;
		return true;
	}

	/**
	 * Renvoie une information ou toutes les informations sur le plugin
	 * @param  string $key Clé de l'info à retourner, ou NULL pour recevoir toutes les infos
	 * @return mixed       Info demandée ou tableau des infos.
	 */
	public function getInfos($key = null)
	{
		if (is_null($key))
		{
			return $this->plugin;
		}

		if (property_exists($this->plugin, $key))
		{
			return $this->plugin->$key;
		}

		return null;
	}

	/**
	 * Renvoie l'identifiant du plugin
	 * @return string Identifiant du plugin
	 */
	public function id()
	{
		return $this->id;
	}

	public function route(bool $public, string $uri): void
	{
		if (!$uri || substr($uri, -1) == '/') {
			$uri .= 'index.php';
		}

		try {
			$this->call($public, $uri);
		}
		catch (\UnexpectedValueException $e) {
			http_response_code(404);
			throw new UserException($e->getMessage());
		}
	}

	/**
	 * Inclure un fichier depuis le plugin (dynamique ou statique)
	 * @param bool   $public TRUE si le fichier est situé dans 'public', sinon dans 'admin'
	 * @param string $file   Chemin du fichier à aller chercher : si c'est un .php il sera inclus,
	 * sinon il sera juste affiché
	 * @return void
	 * @throws UserException Si le fichier n'existe pas ou fait partie des fichiers qui ne peuvent
	 * être appelés que par des méthodes de Plugin.
	 * @throws \RuntimeException Si le chemin indiqué tente de sortir du contexte du PHAR
	 */
	public function call(bool $public, string $file)
	{
		$file = preg_replace('!^[./]*!', '', $file);

		if (preg_match('!(?:\.\.|[/\\\\]\.|\.[/\\\\])!', $file))
		{
			throw new \UnexpectedValueException('Chemin de fichier incorrect.');
		}

		$forbidden = ['install.php', 'paheko_plugin.ini', 'upgrade.php', 'uninstall.php'];

		if (in_array(basename($file), $forbidden))
		{
			throw new UserException('Le fichier ' . $file . ' ne peut être appelé par cette méthode.');
		}

		$path = $this->path();

		if (!$path) {
			throw new UserException('Cette extension n\'est pas disponible.');
		}

		$path .= $public ? '/public/' : '/admin/';
		$path .= $file;

		if (!file_exists($path)) {
			throw new UserException(sprintf('Le fichier "%s" n\'existe pas dans le plugin "%s"', substr($path, strlen($this->path())), $this->id));
		}

		if (is_dir($path)) {
			throw new UserException(sprintf('Sécurité : impossible de lister le répertoire "%s" du plugin "%s".', $file, $this->id));
		}

		if (substr($path, -4) === '.php')
		{
			define('Garradin\PLUGIN_ROOT', $this->path());
			define('Garradin\PLUGIN_URL', WWW_URL . 'p/' . $this->id() . '/');
			define('Garradin\PLUGIN_ADMIN_URL', WWW_URL .'admin/p/' . $this->id() . '/');
			define('Garradin\PLUGIN_QSP', '?');

			// Créer l'environnement d'exécution du plugin
			$plugin = $this;

			if (!$public) {
				require ROOT . '/www/admin/_inc.php';
			}

			$tpl = Template::getInstance();
			$tpl->assign('plugin', $this->getInfos());
			$tpl->assign('plugin_url', PLUGIN_URL);
			$tpl->assign('plugin_admin_url', PLUGIN_ADMIN_URL);
			$tpl->assign('plugin_root', PLUGIN_ROOT);

			include $path;
		}
		else
		{
			// Récupération du type MIME à partir de l'extension
			$pos = strrpos($path, '.');
			$ext = substr($path, $pos+1);

			if (isset($this->mimes[$ext]))
			{
				$mime = $this->mimes[$ext];
			}
			else
			{
				$mime = 'text/plain';
			}

			header('Content-Type: ' .$mime);
			header('Content-Length: ' . filesize($path));
			header('Cache-Control: public, max-age=3600');
			header('Last-Modified: ' . date(DATE_RFC7231, filemtime($path)));

			readfile($path);
		}
	}

	/**
	 * Désinstaller le plugin
	 * @return boolean TRUE si la suppression a fonctionné
	 */
	public function uninstall()
	{
		if (file_exists($this->path() . '/uninstall.php'))
		{
			$plugin = $this;
			include $this->path() . '/uninstall.php';
		}

		$db = DB::getInstance();
		$db->delete('plugins_signals', 'plugin = ?', $this->id);
		return $db->delete('plugins', 'id = ?', $this->id);
	}

	/**
	 * Renvoie TRUE si le plugin a besoin d'être mis à jour
	 * (si la version notée dans la DB est différente de la version notée dans paheko_plugin.ini)
	 * @return boolean TRUE si le plugin doit être mis à jour, FALSE sinon
	 */
	public function needUpgrade()
	{
		$infos = (object) parse_ini_file($this->path() . '/paheko_plugin.ini', false);

		if (version_compare($this->plugin->version, $infos->version, '!=')) {
			return true;
		}

		return false;
	}

	/**
	 * Mettre à jour le plugin
	 * Appelle le fichier upgrade.php dans l'archive si celui-ci existe.
	 * @return boolean TRUE si tout a fonctionné
	 */
	public function upgrade(): void
	{
		$infos = (object) parse_ini_file($this->path() . '/paheko_plugin.ini', false);

		if (!isset($infos->name)) {
			return;
		}

		if (file_exists($this->path() . '/upgrade.php'))
		{
			$plugin = $this;
			include $this->path() . '/upgrade.php';
		}

		$data = [
			'name'		=>	$infos->name,
			'description'=>	$infos->description,
			'author'	=>	$infos->author,
			'url'		=>	$infos->url,
			'version'	=>	$infos->version,
		];

		if ($config = self::getDefaultConfig($this->id, $this->path())) {
			$data['config'] = json_encode($config);
		}

		DB::getInstance()->update('plugins', $data, 'id = :id', ['id' => $this->id]);
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

		// pour empêcher d'appeler des méthodes de Garradin après un import de base de données "hackée"
		if (strpos($callable_name, 'Garradin\\Plugin\\') !== 0)
		{
			throw new \LogicException('Le callback donné n\'utilise pas le namespace Garradin\\Plugin : ' . $callable_name);
		}

		$db = DB::getInstance();

		$callable_name = str_replace('Garradin\\Plugin\\', '', $callable_name);

		$db->preparedQuery('INSERT OR REPLACE INTO plugins_signals VALUES (?, ?, ?);', [$signal, $this->id, $callable_name]);
	}

	public function unregisterSignal(string $signal): void
	{
		DB::getInstance()->preparedQuery('DELETE FROM plugins_signals WHERE plugin = ? AND signal = ?;', [$this->id, $signal]);
	}

	/**
	 * Liste des plugins installés (en DB)
	 * @return array Liste des plugins triés par nom
	 */
	static public function listInstalled()
	{
		$db = DB::getInstance();
		$plugins = $db->getGrouped('SELECT id, * FROM plugins ORDER BY name;');

		foreach ($plugins as &$row)
		{
			$row->disabled = !self::getPath($row->id);
		}

		return $plugins;
	}

	/**
	 * Checks if a plugin requires an upgrade and upgrade it
	 * This is run after an upgrade, a database restoration, or in the Plugins page
	 */
	static public function upgradeAllIfRequired(): bool
	{
		$i = 0;

		// Mettre à jour les plugins si nécessaire
		foreach (self::listInstalled() as $id => $infos)
		{
			// Ne pas tenir compte des plugins dont le code n'est pas dispo
			if ($infos->disabled) {
				continue;
			}

			$plugin = new Plugin($id);

			if ($plugin->needUpgrade()) {
				$plugin->upgrade();
				$i++;
			}

			unset($plugin);
		}

		return $i > 0;
	}

	/**
	 * Liste les plugins qui doivent être affichés dans le menu
	 * @return array Tableau associatif id => nom (ou un tableau vide si aucun plugin ne doit être affiché)
	 */
	static public function listMenu(Session $session)
	{
		$list = [];

		// Let plugins handle their listing
		self::fireSignal('menu.item', compact('session'), $list);
		ksort($list);

		return $list;
	}

	/**
	 * Liste les plugins téléchargés mais non installés
	 * @return array Liste des plugins téléchargés
	 */
	static public function listDownloaded(bool $remove_installed_from_list = true)
	{
		if ($remove_installed_from_list) {
			$installed = self::listInstalled();
		}
		else {
			$installed = [];
		}

		$list = [];

		foreach (glob(PLUGINS_ROOT . '/*') as $file)
		{
			$file = basename($file);

			if (substr($file, 0, 1) == '.')
				continue;

			if (preg_match('!^(' . self::PLUGIN_ID_REGEXP . ')\.tar\.gz$!', $file, $match))
			{
				// Sélectionner les archives PHAR
				$file = $match[1];
			}
			elseif (is_dir(PLUGINS_ROOT . '/' . $file)
				&& preg_match('!^' . self::PLUGIN_ID_REGEXP . '$!', $file)
				&& is_file(sprintf('%s/%s/paheko_plugin.ini', PLUGINS_ROOT, $file)))
			{
				// Rien à faire, le nom valide du plugin est déjà dans "$file"
			}
			else
			{
				// ignorer tout ce qui n'est pas un répertoire ou une archive PHAR valides
				continue;
			}

			if (array_key_exists($file, $installed))
			{
				// Ignorer les plugins déjà installés
				continue;
			}

			$data = (object) parse_ini_file(self::getPath($file) . '/paheko_plugin.ini', false);;

			if (!isset($data->name)) {
				// Ignore old plugins
				continue;
			}

			$list[$file] = $data;
		}

		ksort($list);

		return $list;
	}

	/**
	 * Installer un plugin
	 * @param  string  $id       Identifiant du plugin
	 * @return boolean           TRUE si tout a fonctionné
	 */
	static public function install($id)
	{
		$path = self::getPath($id);

		if (!file_exists($path . '/paheko_plugin.ini'))
		{
			throw new UserException(sprintf('Le plugin "%s" n\'est pas une extension Garradin : fichier paheko_plugin.ini manquant.', $id));
		}

		$infos = (object) parse_ini_file($path . '/paheko_plugin.ini', false);

		$required = ['name', 'description', 'author', 'url', 'version'];

		foreach ($required as $key)
		{
			if (!property_exists($infos, $key))
			{
				throw new \RuntimeException('Le fichier paheko_plugin.ini ne contient pas d\'entrée "'.$key.'".');
			}
		}

		if (!empty($infos->min_version) && !version_compare(garradin_version(), $infos->min_version, '>='))
		{
			throw new UserException('Le plugin '.$id.' nécessite Garradin version '.$infos->min_version.' ou supérieure.');
		}

		if (!empty($infos->max_version) && !version_compare(garradin_version(), $infos->max_version, '>'))
		{
			throw new UserException('Le plugin '.$id.' nécessite Garradin version '.$infos->max_version.' ou inférieure.');
		}

		$config = self::getDefaultConfig($id, $path);

		$data = [
			'id' 		=> 	$id,
			'name'		=>	$infos->name,
			'description'=>	$infos->description,
			'author'	=>	$infos->author,
			'url'		=>	$infos->url,
			'version'	=>	$infos->version,
			'config'	=>	$config ? json_encode($config) : null,
		];

		$db = DB::getInstance();
		$db->begin();
		$db->insert('plugins', $data);

		if (file_exists($path . '/install.php'))
		{
			$plugin = new Plugin($id);
			require $plugin->path() . '/install.php';
		}

		$db->commit();

		return true;
	}

	static protected function getDefaultConfig(string $id, string $path)
	{
		$config = null;

		if (file_exists($path . '/config.json'))
		{
			if (!file_exists($path . '/admin/config.php'))
			{
				throw new \RuntimeException(sprintf('Le plugin "%s" ne comporte pas de fichier admin/config.php
					alors que le plugin nécessite le stockage d\'une configuration.', $id));
			}

			$config = json_decode(file_get_contents($path . '/config.json'));

			if (is_null($config))
			{
				throw new \RuntimeException('config.json invalide. Erreur JSON: ' . json_last_error_msg());
			}
		}

		return $config;
	}

	/**
	 * Renvoie la version installée d'un plugin ou FALSE s'il n'est pas installé
	 * @param  string $id Identifiant du plugin
	 * @return mixed      Numéro de version du plugin ou FALSE
	 */
	static public function getInstalledVersion($id)
	{
		return DB::getInstance()->first('SELECT version FROM plugins WHERE id = ?;', $id);
	}

	/**
	 * Déclenche le signal donné auprès des plugins enregistrés
	 * @param  string $signal Nom du signal
	 * @param  array  $params Paramètres du callback (array ou null)
	 * @return NULL 		  NULL si aucun plugin n'a été appelé,
	 * TRUE si un plugin a été appelé et a arrêté l'exécution,
	 * FALSE si des plugins ont été appelés mais aucun n'a stoppé l'exécution
	 */
	static public function fireSignal($signal, $params = null, &$callback_return = null)
	{
		if (!self::$signals) {
			return null;
		}

		// Process SYSTEM_SIGNALS first
		foreach (SYSTEM_SIGNALS as $system_signal) {
			if (key($system_signal) != $signal) {
				continue;
			}

			if (!is_callable(current($system_signal))) {
				throw new \LogicException(sprintf('System signal: cannot call "%s" for signal "%s"', current($system_signal), key($system_signal)));
			}

			if (true === call_user_func_array(current($system_signal), [&$params, &$callback_return])) {
				return true;
			}
		}

		$list = DB::getInstance()->get('SELECT * FROM plugins_signals WHERE signal = ?;', $signal);

		if (!count($list)) {
			return null;
		}

		if (null === $params) {
			$params = [];
		}

		foreach ($list as $row)
		{
			$path = self::getPath($row->plugin);

			// Ne pas appeler les plugins dont le code n'existe pas/plus,
			if (!$path)
			{
				continue;
			}

			$params['plugin_root'] = $path;

			$return = call_user_func_array('Garradin\\Plugin\\' . $row->callback, [&$params, &$callback_return]);

			if (true === $return) {
				return true;
			}
		}

		return false;
	}
}