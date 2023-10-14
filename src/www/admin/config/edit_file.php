<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;

require __DIR__ . '/_inc.php';

$key = qg('k');

$config = Config::getInstance();

if (!isset(Config::FILES[$key])) {
	throw new UserException('Fichier invalide');
}

$file = $config->file($key);

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
	$content = trim((string) f('content'));
	$config->setFile($key, $content === '' ? null : $content);
	$config->save();

	if (qg('js') !== null) {
		die('{"success":true}');
	}

}, $csrf_key, Utils::getSelfURI());

$tpl->assign(compact('csrf_key', 'file'));

if ($type == 'image') {
	$tpl->display('config/edit_image.tpl');
}
else {
	$content = $file ? $file->fetch() : '';
	$path = Config::FILES[$key];
	$format = $file ? $file->renderFormat() : 'skriv';
	$tpl->assign(compact('content', 'path', 'format'));
	$tpl->display(sprintf('common/files/edit_%s.tpl', $type));
}
