<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;

require __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file) {
	throw new UserException('Fichier inconnu');
}

$trash = qg('trash') !== 'no';

if ($trash && !$file->canMoveToTrash()) {
	throw new UserException('Vous n\'avez pas le droit de mettre ce fichier à la corbeille.');
}
elseif (!$trash && !$file->canDelete()) {
	throw new UserException('Vous n\'avez pas le droit de supprimer ce fichier.');
}

$csrf_key = 'file_delete_' . $file->pathHash();
$parent = $file->parent;

$form->runIf('delete', function () use ($file, $trash) {
	if ($trash) {
		$file->moveToTrash();
	}
	else {
		$file->delete();
	}
}, $csrf_key, '!docs/?path=' . $parent);

$tpl->assign(compact('file', 'csrf_key', 'trash'));

$tpl->display('common/files/delete.tpl');