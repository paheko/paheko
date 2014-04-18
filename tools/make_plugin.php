<?php

if (empty($argv[1]) || empty($argv[2]))
{
    die("Usage : " . basename(__FILE__) . " /path/to/plugin/source /path/to/plugin/archive\n");
}

$plugin_file = $argv[2];
$plugin_file = preg_replace('/\.(?:tar(?:\.gz)?|phar)?$/', '', $plugin_file);

$target = realpath($argv[1]);

if (!file_exists($target . '/garradin_plugin.ini'))
{
	die("ERREUR : Le fichier $target/garradin_plugin.ini est introuvable.\n");
}

$infos = parse_ini_file($target . '/garradin_plugin.ini');

if (!empty($infos['config']))
{
	if (!file_exists($target . '/config.json'))
	{
		die("ERREUR : Le fichier config.json est obligatoire si config=1 dans garradin_plugin.ini.\n");
	}

	if (!file_exists($target . '/www/admin/config.php'))
	{
		die("ERREUR : Le fichier www/admin/config.php est obligatoire si config=1 dans garradin_plugin.ini.\n");
	}
}

$required = ['nom', 'description', 'auteur', 'url', 'version', 'menu', 'config'];

foreach ($required as $key)
{
	if (!array_key_exists($key, $infos))
	{
		die('ERREUR : Le fichier garradin_plugin.ini ne contient pas d\'entrée "'.$key.'".' . "\n");
	}
}

if (!empty($infos['menu']) && !file_exists($target . '/www/admin/index.php'))
{
	die("ERREUR : Le fichier www/admin/index.php est obligatoire quand menu=1\n");
}

@unlink('/tmp/plugin.tar');
@unlink('/tmp/plugin.tar.gz');

$p = new PharData('/tmp/plugin.tar');

$p->buildFromDirectory($target);

$p->compress(Phar::GZ);

@unlink('/tmp/plugin.tar');
rename('/tmp/plugin.tar.gz', $plugin_file . '.tar.gz');
