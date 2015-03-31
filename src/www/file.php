<?php

namespace Garradin;

require __DIR__ . '/_inc.php';

$id = isset($_GET['id']) ? $_GET['id'] : false;
$thumb = !empty($_GET['thumb']) ? true : false;
$filename = !empty($_GET['file']) ? $_GET['file'] : false;

if (empty($id))
{
	throw new UserException('Fichier inconnu.');
}

$id = base_convert($id, 36, 10);

$file = new Fichiers((int)$id);

$membres = new Membres;
$is_logged = $membres->isLogged();

if (!$file->checkAccess($membres->getLoggedUser()))
{
	header('HTTP/1.1 403 Forbidden', true, 403);
	throw new UserException('Vous n\'avez pas accès à ce fichier.');
}

if ($thumb)
{
	$file->serveThumbnail();
}
else
{
	$file->serve();
}
