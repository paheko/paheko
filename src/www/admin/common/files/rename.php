<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Users\Session;

require __DIR__ . '/../../_inc.php';

$session = Session::getInstance();
$file = Files::getByHashID(qg('id'));

if (!$file) {
	throw new UserException('Fichier inconnu');
}

$parent = $file->parent();

if (!$file->canRename($session)) {
	throw new UserException('Vous n\'avez pas le droit de modifier ce fichier.');
}

$csrf_key = 'file_rename_' . $file->pathHash();

$form->runIf('rename', function () use ($file) {
	$file->changeFileName(f('new_name'), Session::getInstance(), true);
}, $csrf_key, '!docs/?id=' . $file->getParentHashID());

$tpl->assign(compact('file', 'csrf_key'));

$tpl->display('common/files/rename.tpl');