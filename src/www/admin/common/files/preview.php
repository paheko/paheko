<?php
namespace Paheko;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;

require __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file) {
	throw new UserException('Ce fichier est introuvable.');
}

if (!$file->canRead()) {
	throw new UserException('Vous n\'avez pas le droit de lire ce fichier.');
}

if ($file->renderFormat()) {
	$tpl->assign('content', $file->render());
	$tpl->assign('file', $file);
	$tpl->display('common/files/_preview.tpl');
}
else if ($html = $file->editorHTML(true)) {
	echo $html;
}
else {
	// We don't need $session here as read access is already checked above
	$file->serve();
}
