<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;

require __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file) {
	throw new UserException('Fichier inconnu');
}

$parent = $file->parent();

if (!$parent->canCreateHere()) {
	throw new UserException('Vous n\'avez pas le droit de modifier ce fichier.');
}

$csrf_key = 'file_rename_' . $file->pathHash();

$form->runIf('rename', function () use ($file) {
	$file->changeFileName(f('new_name'));
}, $csrf_key, '!docs/?path=' . $file->parent);

$tpl->assign(compact('file', 'csrf_key'));

$tpl->display('common/files/rename.tpl');