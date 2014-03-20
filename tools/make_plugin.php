<?php

if (ini_get('phar.readonly'))
{
    die("La variable INI phar.readonly doit être positionnée à Off pour utiliser ce script.\n");
}

if (empty($argv[1]) || empty($argv[2]))
{
    die("Usage : " . basename(__FILE__) . " plugin.phar /path/to/plugin\n");
}

$phar_file = $argv[1];
$phar_name = basename($phar_file);
$target = realpath($argv[2]);

if (!file_exists($target . '/index.php'))
{
	die("ERREUR : Le fichier garradin_plugin.ini est obligatoire.\n");
}

$infos = parse_ini_file($target . '/garradin_plugin.ini');

if (!empty($infos['config']))
{
	if (!file_exists($target . '/config.json'))
	{
		die("ERREUR : Le fichier config.json est obligatoire si config=1 dans garradin_plugin.ini.\n");
	}

	if (!file_exists($target . '/config.php'))
	{
		die("ERREUR : Le fichier config.php est obligatoire si config=1 dans garradin_plugin.ini.\n");
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

if (!file_exists($target . '/index.php'))
{
	die("ERREUR : Le fichier index.php est obligatoire.\n");
}

@unlink($phar_file);
@unlink($phar_file . '.gz');


$p = new Phar($phar_file);

$p->buildFromDirectory($target);

$p->compress(Phar::GZ);

rename($phar_file . '.gz', __DIR__ . '/../src/plugins/' . $phar_name);
