<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Users\Session;

require __DIR__ . '/../../_inc.php';

$path = $_GET['p'] ?? null;

if (!is_string($path)) {
	throw new UserException('Invalid path', 400);
}

File::validatePath($path);

if (!File::canCreate($path)) {
	throw new UserException('Vous n\'avez pas le droit de créer de fichier ici.', 403);
}

$csrf_key = 'upload_file';

$form->runIf('upload', function () use ($path) {
	Files::uploadMultiple($path, 'file', Session::getInstance());
}, $csrf_key, '!docs/?path=' . $path);

$max = intval($_GET['max'] ?? 1);
$multiple = $max > 1;

$tpl->assign(compact('csrf_key', 'multiple'));

$tpl->display('common/files/upload.tpl');
