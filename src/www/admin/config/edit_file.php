<?php
namespace Garradin;

use Garradin\Entities\Files\File;
use Garradin\Files\Files;

require __DIR__ . '/_inc.php';

$key = qg('k');

$config = Config::getInstance();

if (!isset(Config::FILES[$key])) {
	throw new UserException('Fichier invalide');
}

$content = '';
$file = $config->file($key);

if ($file) {
	$content = $file->fetch();
}

$type = Config::FILES_TYPES[$key];
$csrf_key = 'edit_file_' . $key;

$form->runIf('upload', function () use ($key, $config) {
	$config->setFile($key, 'file', true);
	$config->save();
}, $csrf_key, Utils::getSelfURI());

$form->runIf('reset', function () use ($key, $config) {
	$config->setFile($key, null);
	$config->save();
}, $csrf_key, Utils::getSelfURI());

$form->runIf('save', function () use ($key, $config) {
	$content = trim(f('content'));
	$config->setFile($key, $content === '' ? null : $content);
	$config->save();

	if (qg('js') !== null) {
		die('{"success":true}');
	}

}, $csrf_key, Utils::getSelfURI());

$tpl->assign('file', $file);

$tpl->assign(compact('csrf_key', 'content'));

if ($type == 'image') {
	$tpl->display('admin/config/edit_image.tpl');
}
else {
	$tpl->display(sprintf('common/files/edit_%s.tpl', $type));
}
