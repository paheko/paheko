<?php

namespace Garradin;

// Routeur pour l'utilisation avec le serveur web intégré à PHP

const WWW_URI = '/';
//const WWW_URL = '/';

$uri = $_SERVER['REQUEST_URI'];

if (($pos = strpos($uri, '?')) !== false)
{
	$uri = substr($uri, 0, $pos);
}

if (file_exists(__DIR__ . $uri))
{
	return false;
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