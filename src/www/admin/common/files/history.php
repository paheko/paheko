<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;

require __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file) {
	throw new UserException('Fichier inconnu');
}

if (!$file->canWrite()) {
	throw new UserException('Vous n\'avez pas accès à ce fichier.');
}

if ($v = (int)qg('download')) {
	$file->downloadVersion($v);
	return;
}

$csrf_key = 'file_history_' . $file->pathHash();

$form->runIf('restore', function () use ($file) {
	$file->restoreVersion((int)f('restore'));
}, $csrf_key, '!common/files/history.php?msg=RESTORED&p=' . $file->path);

$form->runIf('rename', function () use ($file) {
	$file->renameVersion((int)f('rename'), f('new_name'));
}, $csrf_key, '!common/files/history.php?msg=RENAMED&p=' . $file->path);

$form->runIf('delete', function () use ($file) {
	$file->deleteVersion((int)f('delete'));
}, $csrf_key, '!common/files/history.php?msg=DELETED&p=' . $file->path);

if (qg('rename')) {
	$version = $file->getVersion((int)qg('rename'));
	$version = $version->getVersionMetadata($version);
	$tpl->assign(compact('file', 'csrf_key', 'version'));
	$tpl->display('common/files/history_rename.tpl');
	return;
}

$versions = $file->listVersions();

$tpl->assign(compact('versions', 'file', 'csrf_key'));

$tpl->display('common/files/history.tpl');