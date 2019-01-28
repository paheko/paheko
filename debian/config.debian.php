<?php

namespace Garradin;

if (!empty($_ENV['GARRADIN_STANDALONE']))
{
	$home = $_ENV['HOME'];

	// Config directory
	if (empty($_ENV['XDG_CONFIG_HOME']))
	{
		$_ENV['XDG_CONFIG_HOME'] = $home . '/.config';
	}

	if (!file_exists($_ENV['XDG_CONFIG_HOME'] . '/garradin'))
	{
		mkdir($_ENV['XDG_CONFIG_HOME'] . '/garradin', 0700, true);
	}

	// Data directory: where the data will go
	if (empty($_ENV['XDG_DATA_HOME']))
	{
		$_ENV['XDG_DATA_HOME'] = $home . '/.local/share';
	}

	if (!file_exists($_ENV['XDG_DATA_HOME'] . '/garradin'))
	{
		mkdir($_ENV['XDG_DATA_HOME'] . '/garradin', 0700, true);
	}

	define('Garradin\DATA_ROOT', $_ENV['XDG_DATA_HOME'] . '/garradin');

	// Cache directory: temporary stuff
	if (empty($_ENV['XDG_CACHE_HOME']))
	{
		$_ENV['XDG_CACHE_HOME'] = $home . '/.cache';
	}

	if (!file_exists($_ENV['XDG_CACHE_HOME'] . '/garradin'))
	{
		mkdir($_ENV['XDG_CACHE_HOME'] . '/garradin', 0700, true);
	}

	define('Garradin\CACHE_ROOT', $_ENV['XDG_CACHE_HOME'] . '/garradin');

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

	file_put_contents($last_file, $last_sqlite);

	$secret_file = $_ENV['XDG_CONFIG_HOME'] . '/garradin/secret';

	if (!file_exists($secret_file))
	{
		$random = function_exists('random_bytes') ? random_bytes(64) : mt_rand();
		$random = sha1($random . $secret_file);

		file_put_contents($secret_file, $random);
	}

	define('Garradin\SECRET_KEY', trim(file_get_contents($secret_file)));

	define('Garradin\DB_FILE', $last_sqlite);
	define('Garradin\LOCAL_LOGIN', 1);
}
