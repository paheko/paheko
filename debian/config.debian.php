<?php

namespace Garradin;

if (!empty($_ENV['GARRADIN_STANDALONE']))
{
	$home = $_ENV['HOME'];

	if (empty($_ENV['XDG_CONFIG_HOME']))
	{
		$_ENV['XDG_CONFIG_HOME'] = $home . '/.config';
	}

	if (empty($_ENV['XDG_DATA_HOME']))
	{
		$_ENV['XDG_DATA_HOME'] = $home . '/.local/share';
	}

	if (!file_exists($_ENV['XDG_DATA_HOME'] . '/garradin'))
	{
		mkdir($_ENV['XDG_DATA_HOME'] . '/garradin');
	}

	define('Garradin\DATA_ROOT', $_ENV['XDG_DATA_HOME'] . '/garradin');

	$last_file = $_ENV['XDG_CONFIG_HOME'] . '/garradin/last';

	if ($_ENV['GARRADIN_STANDALONE'] != 1)
	{
		$last_sqlite = trim($_ENV['GARRADIN_STANDALONE']);
	}
	else if (file_exists($last_file))
	{
		$last_sqlite = trim(file_get_contents($last_file));
	}
	else 
	{
		$last_sqlite = $_ENV['XDG_DATA_HOME'] . '/garradin/association.sqlite';
	}

	if (!file_exists($_ENV['XDG_CONFIG_HOME'] . '/garradin'))
	{
		mkdir($_ENV['XDG_CONFIG_HOME'] . '/garradin');
	}

	file_put_contents($last_file, $last_sqlite);
	
	define('Garradin\DB_FILE', $last_sqlite);
	define('Garradin\LOCAL_LOGIN', 1);
}
