<?php
namespace Garradin;

use Garradin\Entities\Files\File;
use Garradin\Files\Files;

require __DIR__ . '/../../_inc.php';

try {
	$file = Files::get((int) qg('id'));
}
catch (\InvalidArgumentException $e) {
	throw new UserException($e->getMessage());
}

if (!$file->checkWriteAccess($session)) {
    throw new UserException('Vous n\'avez pas le droit de modifier ce fichier.');
}

$editor = $file->getEditor();
$csrf_key = 'edit_file_' . $file->id();

if (!$editor) {
	$tpl->assign('file', $file);
	$tpl->display('common/file_upload.tpl');
}
else {
	$content = $file->fetch();
	$tpl->assign(compact('csrf_key', 'content'));
	$tpl->display(sprintf('common/files/edit_%s.tpl', $editor));
}
