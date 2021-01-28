<?php
namespace Garradin;

use Garradin\Entities\Files\File;
use Garradin\Files\Files;

if (!defined('Garradin\ROOT')) {
	die('Access denied.');
}

if (!isset($csrf_key, $redirect)) {
	throw new \InvalidArgumentException('Missing params');
}

try {
	$file = Files::get(qg('id'));
}
catch (\InvalidArgumentException $e) {
	throw new UserException($e->getMessage());
}

if (!$file->checkWriteAccess($session)) {
    throw new UserException('Vous n\'avez pas le droit de supprimer ce fichier.');
}

$form->runIf('delete', function () use ($file) {
	$file->delete();
}, $csrf_key, $redirect);

$tpl->assign(compact('file', 'csrf_key'));

$tpl->display('common/delete_file.tpl');