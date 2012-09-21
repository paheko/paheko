<?php

if (!version_compare(phpversion(), '5.3.0', '>='))
{
	die('PHP 5.3.0 ou supérieur est nécessaire au fonctionnement de Garradin.');
}

define('GARRADIN_INSTALL_PROCESS', true);
require __DIR__ . '/include/init.php';

if (!defined('PHP_SAPI'))
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

		header('Location: '.WWW_URL);
	}
	else
	{
		file_put_contents(GARRADIN_ROOT . '/config.local.php', '<?php define(\'WWW_URI\', \''.$uri.'\'); ?>');
		header('Location: '.$url);
	}
}
else
{
	header('Location: '.WWW_URL);
}

?>