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
		return $db->simpleStatement('SELECT * FROM plugins ORDER BY nom;');
	}

	static public function fetchOfficialList()
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
			$result = file_get_contents(GARRADIN_PLUGINS_URL, NULL, $context);
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

		return ($hash == $list[$id]['hash']);
	}

	static public function isOfficial($id)
	{
		$list = self::fetchOfficialList();
		return array_key_exists($id, $list);
	}
}