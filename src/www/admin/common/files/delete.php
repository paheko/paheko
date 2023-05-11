<?php
namespace Garradin;

use Garradin\Entities\Files\File;
use Garradin\Files\Files;

require __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file) {
	throw new UserException('Fichier inconnu');
}

if (!$file->canDelete()) {
	throw new UserException('Vous n\'avez pas le droit de supprimer ce fichier.');
}

$csrf_key = 'file_delete_' . $file->pathHash();
$parent = $file->parent;

$form->runIf('delete', function () use ($file) {
	$file->moveToTrash();
}, $csrf_key, '!docs/?path=' . $parent);

$tpl->assign(compact('file', 'csrf_key'));

$tpl->display('common/files/delete.tpl');