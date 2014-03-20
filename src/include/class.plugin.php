<?php

namespace Garradin;

class Plugin
{
	protected $id = null;
	protected $plugin = null;

	protected $mimes = [
		'css' => 'text/css',
		'gif' => 'image/gif',
		'htm' => 'text/html',
		'html' => 'text/html',
		'ico' => 'image/x-ico',
		'jpe' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'js' => 'application/x-javascript',
		'pdf' => 'application/pdf',
		'png' => 'image/png',
		'swf' => 'application/shockwave-flash',
		'xml' => 'text/xml',
		'svg' => 'image/svg+xml',
	];

	/**
	 * Construire un objet Plugin pour un plugin
	 * @param string $id Identifiant du plugin
	 * @throws UserException Si le plugin n'est pas installé (n'existe pas en DB)
	 */
	public function __construct($id)
	{
		$db = DB::getInstance();
		$this->plugin = $db->simpleQuerySingle('SELECT * FROM plugins WHERE id = ?;', true, $id);

		if (!$this->plugin)
		{
			throw new UserException('Ce plugin n\'existe pas ou n\'est pas installé correctement.');
		}

		$this->plugin['config'] = json_decode($this->plugin['config'], true);
		
		if (!is_array($this->plugin['config']))
		{
			$this->plugin['config'] = [];
		}

		$this->id = $id;
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
			return $this->plugin['config'];
		}

		if (array_key_exists($key, $this->plugin['config']))
		{
			return $this->plugin['config'][$key];
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
			unset($this->plugin['config'][$key]);
		}
		else
		{
			$this->plugin['config'][$key] = $value;
		}

		$db = DB::getInstance();
		$db->simpleUpdate('plugins', 
			['config' => json_encode($this->plugin['config'])],
			'id = \'' . $this->id . '\'');

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

		if (array_key_exists($key, $this->plugin))
		{
			return $this->plugin[$key];
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

	/**
	 * Inclure un fichier depuis le plugin (dynamique ou statique)
	 * @param  string $file Chemin du fichier à aller chercher : si c'est un .php il sera inclus,
	 * sinon il sera juste affiché
	 * @return void
	 * @throws UserException Si le fichier n'existe pas ou fait partie des fichiers qui ne peuvent
	 * être appelés que par des méthodes de Plugin.
	 * @throws RuntimeException Si le chemin indiqué tente de sortir du contexte du PHAR
	 */
	public function call($file)
	{
		$file = preg_replace('!^[./]*!', '', $file);

		if (preg_match('!(?:\.\.|[/\\]\.|\.[/\\])!', $file))
		{
			throw new \RuntimeException('Chemin de fichier incorrect.');
		}

		$forbidden = ['install.php', 'garradin_plugin.ini', 'upgrade.php', 'uninstall.php', 'signals.php'];

		if (in_array($file, $forbidden))
		{
			throw new UserException('Le fichier ' . $file . ' ne peut être appelé par cette méthode.');
		}

		if (!file_exists('phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/' . $file))
		{
			throw new UserException('Le fichier ' . $file . ' n\'existe pas dans le plugin ' . $this->id);
		}

		$plugin = $this;
		global $tpl, $config, $user, $membres;

		if (substr($file, -4) === '.php')
		{
			include 'phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/' . $file;
		}
		else
		{
			// Récupération du type MIME à partir de l'extension
			$ext = substr($file, strrpos($file, '.')+1);

			if (isset($this->mimes[$ext]))
			{
				$mime = $this->mimes[$ext];
			}
			else
			{
				$mime = 'text/plain';
			}

			header('Content-Type: ' .$this->mimes[$ext]);
			header('Content-Length: ' . filesize('phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/' . $file));

			readfile('phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/' . $file);
		}
	}

	/**
	 * Désinstaller le plugin
	 * @return boolean TRUE si la suppression a fonctionné
	 */
	public function uninstall()
	{
		if (file_exists('phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/uninstall.php'))
		{
			include 'phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/uninstall.php';
		}
		
		unlink(PLUGINS_PATH . '/' . $this->id . '.phar');

		$db = DB::getInstance();
		return $db->simpleExec('DELETE FROM plugins WHERE id = ?;', $this->id);
	}

	/**
	 * Renvoie TRUE si le plugin a besoin d'être mis à jour
	 * (si la version notée dans la DB est différente de la version notée dans garradin_plugin.ini)
	 * @return boolean TRUE si le plugin doit être mis à jour, FALSE sinon
	 */
	public function needUpgrade()
	{
		$infos = parse_ini_file('phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/garradin_plugin.ini', false);
		
		if (version_compare($this->plugin['version'], $infos['version'], '!='))
			return true;

		return false;
	}

	/**
	 * Mettre à jour le plugin
	 * Appelle le fichier upgrade.php dans l'archive si celui-ci existe.
	 * @return boolean TRUE si tout a fonctionné
	 */
	public function upgrade()
	{
		if (file_exists('phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/upgrade.php'))
		{
			include 'phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/upgrade.php';
		}

		$db = DB::getInstance();
		return $db->simpleUpdate('plugins', 
			'id = \''.$db->escapeString($this->id).'\'', 
			['version' => $infos['version']]);
	}

	/**
	 * Liste des plugins installés (en DB)
	 * @return array Liste des plugins triés par nom
	 */
	static public function listInstalled()
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetchAssocKey('SELECT id, * FROM plugins ORDER BY nom;');
	}

	/**
	 * Liste les plugins qui doivent être affichés dans le menu
	 * @return array Tableau associatif id => nom (ou un tableau vide si aucun plugin ne doit être affiché)
	 */
	static public function listMenu()
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetchAssoc('SELECT id, nom FROM plugins WHERE menu = 1 ORDER BY nom;');
	}

	/**
	 * Liste les plugins téléchargés mais non installés
	 * @return array Liste des plugins téléchargés
	 */
	static public function listDownloaded()
	{
		$installed = self::listInstalled();

		$list = [];
		$dir = dir(PLUGINS_PATH);

		while ($file = $dir->read())
		{
			if (substr($file, 0, 1) == '.')
				continue;

			if (!preg_match('!^([a-z0-9_-]+)\.phar$!', $file, $match))
				continue;
			
			if (array_key_exists($match[1], $installed))
				continue;

			$list[$match[1]] = parse_ini_file('phar://' . PLUGINS_PATH . '/' . $match[1] . '.phar/garradin_plugin.ini', false);
		}

		$dir->close();

		return $list;
	}

	/**
	 * Liste des plugins officiels depuis le repository signé
	 * @return array Liste des plugins
	 */
	static public function listOfficial()
	{
		// La liste est stockée en cache une heure pour ne pas tuer le serveur distant
		if (Static_Cache::expired('plugins_list', 3600 * 24))
		{
			$url = parse_url(PLUGINS_URL);

			$context_options = [
				'ssl' => [
					'verify_peer'   => TRUE,
					// On vérifie en utilisant le certificat maître de CACert
					'cafile'        => ROOT . '/include/data/cacert.pem',
					'verify_depth'  => 5,
					'CN_match'      => $url['host'],
				]
			];

			$context = stream_context_create($context_options);

			try {
				$result = file_get_contents(PLUGINS_URL, NULL, $context);
			}
			catch (\Exception $e)
			{
				throw new UserException('Le téléchargement de la liste des plugins a échoué : ' . $e->getMessage());
			}

			Static_Cache::store('plugins_list', $result);
		}
		else
		{
			$result = Static_Cache::get('plugins_list');
		}

		$list = json_decode($result, true);
		return $list;
	}

	/**
	 * Vérifier le hash du plugin $id pour voir s'il correspond au hash du fichier téléchargés
	 * @param  string $id Identifiant du plugin
	 * @return boolean    TRUE si le hash correspond (intégrité OK), sinon FALSE
	 */
	static public function checkHash($id)
	{
		$list = self::fetchOfficialList();

		if (!array_key_exists($id, $list))
			return null;

		$hash = sha1_file(PLUGINS_PATH . '/' . $id . '.phar');

		return ($hash === $list[$id]['hash']);
	}

	/**
	 * Est-ce que le plugin est officiel ?
	 * @param  string  $id Identifiant du plugin
	 * @return boolean     TRUE si le plugin est officiel, FALSE sinon
	 */
	static public function isOfficial($id)
	{
		$list = self::fetchOfficialList();
		return array_key_exists($id, $list);
	}

	/**
	 * Télécharge un plugin depuis le repository officiel, et l'installe
	 * @param  string $id Identifiant du plugin
	 * @return boolean    TRUE si ça marche
	 * @throws LogicException Si le plugin n'est pas dans la liste des plugins officiels
	 * @throws UserException Si le plugin est déjà installé ou que le téléchargement a échoué
	 * @throws RuntimeException Si l'archive téléchargée est corrompue (intégrité du hash ne correspond pas)
	 */
	static public function download($id)
	{
		$list = self::fetchOfficialList();

		if (!array_key_exists($id, $list))
		{
			throw new \LogicException($id . ' n\'est pas un plugin officiel (absent de la liste)');
		}

		if (file_exists(PLUGINS_PATH . '/' . $id . '.phar'))
		{
			throw new UserException('Le plugin '.$id.' existe déjà.');
		}

		$url = parse_url(PLUGINS_URL);

		$context_options = [
			'ssl' => [
				'verify_peer'   => TRUE,
				'cafile'        => ROOT . '/include/data/cacert.pem',
				'verify_depth'  => 5,
				'CN_match'      => $url['host'],
			]
		];

		$context = stream_context_create($context_options);

		try {
			copy($list[$id]['phar'], PLUGINS_PATH . '/' . $id . '.phar', $context);
		}
		catch (\Exception $e)
		{
			throw new UserException('Le téléchargement du plugin '.$id.' a échoué : ' . $e->getMessage());
		}

		if (!self::checkHash($id))
		{
			unlink(PLUGINS_PATH . '/' . $id . '.phar');
			throw new \RuntimeException('L\'archive du plugin '.$id.' est corrompue (le hash SHA1 ne correspond pas).');
		}

		self::install($id, true);

		return true;
	}

	/**
	 * Installer un plugin
	 * @param  string  $id       Identifiant du plugin
	 * @param  boolean $official TRUE si le plugin est officiel
	 * @return boolean           TRUE si tout a fonctionné
	 */
	static public function install($id, $official = false)
	{
		if (!file_exists('phar://' . PLUGINS_PATH . '/' . $id . '.phar'))
		{
			throw new \RuntimeException('Le plugin ' . $id . ' ne semble pas exister et ne peut donc être installé.');
		}

		if (!file_exists('phar://' . PLUGINS_PATH . '/' . $id . '.phar/garradin_plugin.ini'))
		{
			throw new UserException('L\'archive '.$id.'.phar n\'est pas une extension Garradin : fichier garradin_plugin.ini manquant.');
		}

		if (!file_exists('phar://' . PLUGINS_PATH . '/' . $id . '.phar/index.php'))
		{
			throw new \RuntimeException('L\'archive '.$id.'.phar ne comporte pas de fichier index.php : est-ce un plugin Garradin ?');
		}

		$infos = parse_ini_file('phar://' . PLUGINS_PATH . '/' . $id . '.phar/garradin_plugin.ini', false);

		$required = ['nom', 'description', 'auteur', 'url', 'version', 'menu', 'config'];

		foreach ($required as $key)
		{
			if (!array_key_exists($key, $infos))
			{
				throw new \RuntimeException('Le fichier garradin_plugin.ini ne contient pas d\'entrée "'.$key.'".');
			}
		}

		$config = '';

		if ((bool)$infos['config'])
		{
			if (!file_exists('phar://' . PLUGINS_PATH . '/' . $id . '.phar/config.json'))
			{
				throw new \RuntimeException('L\'archive '.$id.'.phar ne comporte pas de fichier config.json 
					alors que le plugin nécessite le stockage d\'une configuration.');
			}

			if (!file_exists('phar://' . PLUGINS_PATH . '/' . $id . '.phar/config.php'))
			{
				throw new \RuntimeException('L\'archive '.$id.'.phar ne comporte pas de fichier config.php 
					alors que le plugin nécessite le stockage d\'une configuration.');
			}

			$config = json_encode(file_get_contents('phar://' . PLUGINS_PATH . '/' . $id . '.phar/config.json'));
		}

		$db = DB::getInstance();
		$db->simpleInsert('plugins', [
			'id' 		=> 	$id,
			'officiel' 	=> 	(int)(bool)$official,
			'nom'		=>	$infos['nom'],
			'description'=>	$infos['description'],
			'auteur'	=>	$infos['auteur'],
			'url'		=>	$infos['url'],
			'version'	=>	$infos['version'],
			'menu'		=>	(int)(bool)$infos['menu'],
			'config'	=>	$config,
		]);

		if (file_exists('phar://' . PLUGINS_PATH . '/' . $id . '.phar/install.php'))
		{
			include 'phar://' . PLUGINS_PATH . '/' . $id . '.phar/install.php';
		}

		return true;
	}
}