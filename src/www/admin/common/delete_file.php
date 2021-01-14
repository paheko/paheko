<?php
namespace Garradin;

use Garradin\Services\Services;

if (!defined('Garradin\ROOT')) {
	die('Access denied.');
}

if (!isset($csrf_key, $redirect)) {
	throw new \InvalidArgumentException('Missing params');
}

try {
	$file = new Fichiers(qg('id'));
}
catch (\InvalidArgumentException $e) {
	throw new UserException($e->getMessage());
}

if (!$file->checkAccess($session))
{
    throw new UserException('Vous n\'avez pas accès à ce fichier.');
}

$form->runIf('delete', function () use ($file) {
	$file->remove();
}, $csrf_key, $redirect);

$tpl->assign(compact('file', 'csrf_key'));

$tpl->display('common/delete_file.tpl');