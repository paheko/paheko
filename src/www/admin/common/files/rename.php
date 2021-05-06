<?php
namespace Garradin;

use Garradin\Entities\Files\File;
use Garradin\Files\Files;

require __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file) {
	throw new UserException('Fichier inconnu');
}

if (!$file->checkWriteAccess($session)) {
    throw new UserException('Vous n\'avez pas le droit de modifier ce fichier.');
}

$context = $file->context();

if ($context == File::CONTEXT_CONFIG || $context == File::CONTEXT_WEB) {
	throw new UserException('Vous n\'avez pas le droit de renommer ce fichier.');
}

$csrf_key = 'file_rename_' . $file->pathHash();

$form->runIf('rename', function () use ($file) {
	$file->changeFileName(f('new_name'));
}, $csrf_key, '!');

$tpl->assign(compact('file', 'csrf_key'));

$tpl->display('common/files/rename.tpl');