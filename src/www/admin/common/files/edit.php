<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\UserTemplate\Modules;

require __DIR__ . '/../../_inc.php';

$path = qg('p');
$file = Files::get($path);
$content = null;

if (!$file && Files::getContext($path) == File::CONTEXT_MODULES && File::canCreate($path)
	&& ($content = Modules::fetchDistFile($path)) && null !== $content) {
	$file = Files::createObject($path);
}
elseif (!$file) {
	throw new UserException('Fichier inconnu');
}

if (!$file->canWrite()) {
	throw new UserException('Vous n\'avez pas le droit de modifier ce fichier.');
}

$editor = $file->editorType();
$csrf_key = 'edit_file_' . $file->pathHash();

$form->runIf('content', function () use ($file) {
	$file->setContent(f('content'));
}, $csrf_key, Utils::getSelfURI());

$tpl->assign(compact('csrf_key', 'file'));

$fallback = qg('fallback');

if (!$editor && $fallback) {
	$editor = $fallback;
}

if (!$editor) {
	$tpl->display('common/files/upload.tpl');
}
elseif ($editor == 'wopi') {
	echo $file->editorHTML();
}
else {
	$content ??= $file->fetch();
	$path = $file->path;
	$format = $file->renderFormat();
	$tpl->assign(compact('csrf_key', 'content', 'path', 'format'));
	$tpl->display(sprintf('common/files/edit_%s.tpl', $editor));
}
