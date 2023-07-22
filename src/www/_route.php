<?php

namespace Paheko;

use Paheko\Web\Router;

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

if (($pos = strpos($uri, '?')) !== false)
{
	$uri = substr($uri, 0, $pos);
}

if (file_exists(__DIR__ . $uri))
{
	if (PHP_SAPI != 'cli-server') {
		http_response_code(500);
		die('Erreur de configuration du serveur web: cette URL ne devrait pas être traitée par Paheko');
	}

	return false;
}
else
{
	require __DIR__ . '/../include/init.php';
	Router::route();
}
