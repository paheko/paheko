<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;
use Paheko\Users\Session;

require __DIR__ . '/../../_inc.php';

$parent = qg('p');

if (!File::canCreate($parent)) {
	throw new UserException('Vous n\'avez pas le droit de créer de fichier ici.', 403);
}

$csrf_key = 'upload_file_' . md5($parent);

$form->runIf('upload', function () use ($parent) {
	Files::uploadMultiple($parent, 'file', Session::getInstance());
}, $csrf_key, '!docs/?path=' . $parent);

$max = (int) qg('max');
$multiple = $max > 1;

$tpl->assign(compact('parent', 'csrf_key', 'multiple'));

$tpl->display('common/files/upload.tpl');
