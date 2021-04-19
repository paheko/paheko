<?php

namespace Garradin;

if (empty($_SERVER['REQUEST_URI'])) {
	http_response_code(500);
	die('Appel non supporté');
}

$uri = $_SERVER['REQUEST_URI'];

if ('_route.php' === basename($uri)) {
	http_response_code(403);
	die('Appel interdit');
}

http_response_code(200);

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
		http_response_code(500);
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
else
{
	require __DIR__ . '/index.php';
}