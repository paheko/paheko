<?php

if (!version_compare(phpversion(), '5.3.0', '>='))
{
	die('PHP 5.3.0 ou supérieur est nécessaire au fonctionnement de Garradin.');
}

define('GARRADIN_INSTALL_PROCESS', true);
require __DIR__ . '/include/init.php';

if (!defined('PHP_SAPI') || file_exists(GARRADIN_ROOT . '/.garradinRootProcessed'))
{
	header('Location: '.WWW_URL);
	exit;
}

if (preg_match('/^apache/', PHP_SAPI))
{
	if (!file_exists(__DIR__ . '/.htaccess'))
	{
		file_put_contents(__DIR__ . '/.htaccess',
			"RewriteEngine On\n" .
			"RewriteCond %{REQUEST_URI} !^".WWW_URI."\n" .
			"RewriteRule ^(.*)$ ".WWW_URI."$1 [QSA,L]\n");
	}

	$uri = dirname(WWW_URI);

	if (substr($uri, -1) != '/')
	{
		$uri .= '/';
	}

	$url = 'http' . (!empty($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $uri;

	$headers = get_headers($url . 'admin/login.php');

	if (!preg_match('!^HTTP/[0-9.]+ 200!', $headers[0]))
	{
		file_put_contents(__DIR__ . '/.htaccess',
			"RedirectMatch ".dirname(WWW_URI)."/((?!www).*) ".WWW_URI."$1\n");

		$headers = get_headers(WWW_URL);

		if (!preg_match('!^HTTP/[0-9.]+ 302!', $headers[0]))
		{
			unlink(__DIR__ . '/.htaccess');
		}
		else
		{
			touch(GARRADIN_ROOT . '/.garradinRootProcessed');
			header('Location: '.WWW_URL);
			exit;
		}
	}
	else
	{
		touch(GARRADIN_ROOT . '/.garradinRootProcessed');
		file_put_contents(GARRADIN_ROOT . '/config.local.php', '<?php define(\'WWW_URI\', \''.$uri.'\'); ?>');
		header('Location: '.$url);
		exit;
	}
}

// Si serveur non Apache, ou ni RewriteRule ni RedirectMatch ne fonctionnent,
// on empêche quand même de lister les répertoires
$dir = dir(GARRADIN_ROOT);

while ($file = $dir->read())
{
	if ($file[0] == '.')
	{
		continue;
	}

	if (!is_dir(GARRADIN_ROOT . '/' . $file) || $file == 'www')
	{
		continue;
	}

	file_put_contents(GARRADIN_ROOT . '/' . $file . '/index.html',
		'<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' .
		'<html><head><title>404 Not Found</title></head><body>' .
		'<h1>Not Found</h1><p>The requested URL was not found on this server.</p>' .
		'</body></html>');
}

$dir->close();

touch(GARRADIN_ROOT . '/.garradinRootProcessed');
header('Location: '.WWW_URL);

?>