<?php

// Ce fichier n'est pas censé être appelé sauf si l'installation de Garradin
// n'est pas effectuée correctement avec le vhost pointant sur le répertoire www/
// auquel cas on limite les dégâts

// Juste une vérification avant de continuer
if (!version_compare(phpversion(), '5.4.0', '>='))
{
	die('PHP 5.4.0 ou supérieur est nécessaire au fonctionnement de Garradin.');
}

if (file_exists(__DIR__ . '/.garradinRootProcessed'))
{
	header('Location: www/');
	exit;
}

// on empêche de lister les répertoires qui ne sont pas censés être publics
$dir = dir(__DIR__);

while ($file = $dir->read())
{
	if ($file[0] == '.')
	{
		continue;
	}

	if (!is_dir(__DIR__ . '/' . $file) || $file == 'www')
	{
		continue;
	}

	file_put_contents(__DIR__ . '/' . $file . '/index.html',
		'<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' .
		'<html><head><title>404 Not Found</title></head><body>' .
		'<h1>Not Found</h1><p>The requested URL was not found on this server.</p>' .
		'</body></html>');
}

$dir->close();

touch(GARRADIN_ROOT . '/.garradinRootProcessed');
header('Location: www/');

?>