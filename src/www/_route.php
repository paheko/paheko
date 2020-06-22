<?php

namespace Garradin;

if (empty($_SERVER['REQUEST_URI'])) {
	die('Appel non supporté');
}

$uri = $_SERVER['REQUEST_URI'];

if ('_route.php' === basename($uri)) {
	die('Appel interdit');
}

if ('favicon.ico' === basename($uri)) {
	die('');
}

if (($pos = strpos($uri, '?')) !== false)
{
	$uri = substr($uri, 0, $pos);
}

if (file_exists(__DIR__ . $uri))
{
	if (PHP_SAPI != 'cli-server') {
		die('Erreur de configuration du serveur web: cette URL ne devrait pas être traitée par Garradin');
	}

	return false;
}
elseif (preg_match('!/p/(.+?)/(.*)!', $uri, $match))
{
	$_GET['_p'] = $match[1];
	$_GET['_u'] = $match[2];
	require __DIR__ . '/plugin.php';
}
elseif (preg_match('!/admin/plugin/(.+?)/(.*)!', $uri, $match))
{
	$_GET['_p'] = $match[1];
	$_GET['_u'] = $match[2];
	require __DIR__ . '/admin/plugin.php';
}
elseif (preg_match('!/f/([\d\w]+)/(.+)!', $uri, $match))
{
	$_GET['id'] = $match[1];
	$_GET['file'] = $match[2];
	require __DIR__ . '/file.php';
}
else
{
	require __DIR__ . '/index.php';
}