<?php

namespace Garradin;

class Plugin
{
	protected $id = null;
	protected $plugin = null;

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

	public function id()
	{
		return $this->id;
	}

	public function call($file)
	{
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

		include 'phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/' . $file;
	}

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

	public function needUpgrade()
	{
		$infos = parse_ini_file('phar://' . PLUGINS_PATH . '/' . $this->id . '.phar/garradin_plugin.ini', false);
		
		if (version_compare($this->plugin['version'], $infos['version'], '!='))
			return true;

		return false;
	}

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

	static public function listInstalled()
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetchAssocKey('SELECT id, * FROM plugins ORDER BY nom;');
	}

	static public function listMenu()
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetchAssoc('SELECT id, nom FROM plugins WHERE menu = 1 ORDER BY nom;');
	}

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

	static public function listOfficial()
	{
		if (Static_Cache::expired('plugins_list', 3600 * 24))
		{
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

	static public function checkHash($id)
	{
		$list = self::fetchOfficialList();

		if (!array_key_exists($id, $list))
			return null;

		$hash = sha1_file(PLUGINS_PATH . '/' . $id . '.phar');

		return ($hash === $list[$id]['hash']);
	}

	static public function isOfficial($id)
	{
		$list = self::fetchOfficialList();
		return array_key_exists($id, $list);
	}

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

	static public function install($id, $official = true)
	{
		if (!file_exists('phar://' . PLUGINS_PATH . '/' . $id . '.phar'))
		{
			throw new \RuntimeException('Le plugin ' . $id . ' ne semble pas exister et ne peut donc être installé.');
		}

		if ($official && !self::checkHash($id))
		{
			throw new \RuntimeException('L\'archive du plugin '.$id.' est corrompue (le hash SHA1 ne correspond pas).');
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