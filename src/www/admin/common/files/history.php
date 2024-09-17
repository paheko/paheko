<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;

require __DIR__ . '/../../_inc.php';

$file = Files::getByHashID(qg('id'));

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
	$new = $file->restoreVersion((int)f('restore'));
	Utils::redirectSelf('!common/files/history.php?msg=RESTORED&id=' . $new->hash_id);
}, $csrf_key);

$form->runIf('rename', function () use ($file) {
	$file->renameVersion((int)f('rename'), f('new_name'));
	Utils::redirectSelf('!common/files/history.php?msg=RENAMED&id=' . $file->hash_id);
}, $csrf_key);

$form->runIf('delete', function () use ($file) {
	$file->deleteVersion((int)f('delete'));
	Utils::redirectSelf('!common/files/history.php?msg=DELETED&id=' . $file->hash_id);
}, $csrf_key);

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