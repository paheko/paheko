<?php

namespace Garradin;

require __DIR__ . '/_inc.php';

$id = isset($_GET['id']) ? $_GET['id'] : false;
$filename = !empty($_GET['file']) ? $_GET['file'] : false;
$size = false;

if (empty($id))
{
	throw new UserException('Fichier inconnu.');
}

foreach ($_GET as $key=>$value)
{
	if (substr($key, -2) == 'px')
	{
		$size = (int)substr($key, 0, -2);
		break;
	}
}

$id = base_convert($id, 36, 10);

try {
	$file = new Fichiers((int)$id);
}
catch (\InvalidArgumentException $e)
{
	throw new UserException('Ce fichier n\'existe pas.');
}

$membres = new Membres;
$is_logged = $membres->isLogged();

if (!$file->checkAccess($membres->getLoggedUser()))
{
	header('HTTP/1.1 403 Forbidden', true, 403);
	throw new UserException('Vous n\'avez pas accès à ce fichier.');
}

if ($size)
{
	$file->serveThumbnail($size);
}
else
{
	$file->serve();
}
