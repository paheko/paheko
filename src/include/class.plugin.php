<?php

namespace Garradin;

class Plugin
{
	protected $id = null;
	protected $config = null;

	public function __construct($id)
	{
		$this->id = null;
	}

	public function getConfig($key = null)
	{
		if (is_null($this->config))
		{
			$db = DB::getInstance();
			$this->config = $db->simpleQuerySingle('SELECT config FROM plugins WHERE id = ?;', false, $this->id);
			$this->config = json_decode($this->config, true);

			if (!is_array($this->config))
			{
				$this->config = [];
			}
		}

		if (array_key_exists($key, $this->config))
		{
			return $this->config[$key];
		}

		return null;
	}

	public function setConfig($key, $value = null)
	{
		$this->getConfig();

		if (is_null($value))
		{
			unset($this->config[$key]);
		}
		else
		{
			$this->config[$key] = $value;
		}

		$db = DB::getInstance();
		$db->simpleUpdate('plugins', 'id = \'' . $this->id . '\'', ['config' => json_encode($this->config)]);

		return true;
	}

	public function getInfos()
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetch('SELECT * FROM plugins WHERE id = ?;', $this->id);
	}

	static public function listInstalled()
	{
		$db = DB::getInstance();
		return $db->simpleStatementFetchAssocKey('SELECT id, * FROM plugins ORDER BY nom;');
	}

	static public function listDownloaded()
	{
		$installed = self::listInstalled();

		$list = [];
		$dir = dir(GARRADIN_PLUGINS_PATH);

		while ($file = $dir->read())
		{
			if (substr($file, 0, 1) == '.')
				continue;

			if (!preg_match('!^([a-z0-9_-]+)\.phar$!', $file, $match))
				continue;
			
			if (array_key_exists($match[1], $installed))
				continue;

			$list[$match[1]] = parse_ini_file('phar://' . GARRADIN_PLUGINS_PATH . '/' . $match[1] . '.phar/infos.ini', false);
		}

		$dir->close();

		return $list;
	}

	static public function listOfficial()
	{
		if (Static_Cache::expired('plugins_list', 3600 * 24))
		{
			$url = parse_url(GARRADIN_PLUGINS_URL);

			$context_options = [
				'ssl' => [
					'verify_peer'   => TRUE,
					'cafile'        => GARRADIN_ROOT . '/include/data/cacert.pem',
					'verify_depth'  => 5,
					'CN_match'      => $url['host'],
				]
			];

			$context = stream_context_create($context_options);

			try {
				$result = file_get_contents(GARRADIN_PLUGINS_URL, NULL, $context);
			}
			catch (\Exception $e)
			{
				throw new UserException('Le téléchargement de la liste des plugins a échoué : ' . $e->getMessage())
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

		$hash = sha1_file(GARRADIN_PLUGINS_PATH . '/' . $id . '.phar');

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

		if (file_exists(GARRADIN_PLUGINS_PATH . '/' . $id . '.phar'))
		{
			throw new UserException('Le plugin '.$id.' existe déjà.');
		}

		$url = parse_url(GARRADIN_PLUGINS_URL);

		$context_options = [
			'ssl' => [
				'verify_peer'   => TRUE,
				'cafile'        => GARRADIN_ROOT . '/include/data/cacert.pem',
				'verify_depth'  => 5,
				'CN_match'      => $url['host'],
			]
		];

		$context = stream_context_create($context_options);

		try {
			copy($list[$id]['phar'], GARRADIN_PLUGINS_PATH . '/' . $id . '.phar', $context);
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Le téléchargement du plugin '.$id.' a échoué : ' . $e->getMessage())
		}

		if (!self::checkHash($id))
		{
			unlink(GARRADIN_PLUGINS_PATH . '/' . $id . '.phar');
			throw new \RuntimeException('L\'archive du plugin '.$id.' est corrompue (le hash SHA1 ne correspond pas).');
		}

		self::install($id, true);

		return true;
	}

	static public function install($id, $official = true)
	{
		if ($official && !self::checkHash($id))
		{
			throw new \RuntimeException('L\'archive du plugin '.$id.' est corrompue (le hash SHA1 ne correspond pas).');
		}

		if (file_exists('phar://' . GARRADIN_PLUGINS_PATH . '/' . $id . '.phar/install.php'))
		{
			include 'phar://' . GARRADIN_PLUGINS_PATH . '/' . $id . '.phar/install.php';
		}

		$infos = parse_ini_file('phar://' . GARRADIN_PLUGINS_PATH . '/' . $id . '.phar/infos.ini', false);

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
			'config'	=>	'',
		]);
	}
	
	static public function uninstall($id)
	{
		if (file_exists('phar://' . GARRADIN_PLUGINS_PATH . '/' . $id . '.phar/uninstall.php'))
		{
			include 'phar://' . GARRADIN_PLUGINS_PATH . '/' . $id . '.phar/uninstall.php';
		}
		
		$db = DB::getInstance();
		return $db->simpleExec('DELETE FROM plugins WHERE id = ?;', $id);
	}

	static public function needUpgrade($id)
	{
		$infos = parse_ini_file('phar://' . GARRADIN_PLUGINS_PATH . '/' . $id . '.phar/infos.ini', false);
		$version = $db->simpleQuerySingle('SELECT version FROM plugins WHERE id = ?;', false, $id);

		if (version_compare($version, $infos['version'], '!='))
			return true;

		return false;
	}

	static public function upgrade($id)
	{
		if (file_exists('phar://' . GARRADIN_PLUGINS_PATH . '/' . $id . '.phar/upgrade.php'))
		{
			include 'phar://' . GARRADIN_PLUGINS_PATH . '/' . $id . '.phar/upgrade.php';
		}

		$db = DB::getInstance();
		return $db->simpleUpdate('plugins', 'id = \''.$db->escapeString($id).'\'', ['version' => $infos['version']]);
	}
}