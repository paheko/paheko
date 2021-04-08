<?php
namespace Garradin;

use Garradin\Entities\Files\File;
use Garradin\Files\Files;

require __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file) {
	throw new UserException('Ce fichier est introuvable.');
}

if (!$file->checkReadAccess($session)) {
	throw new UserException('Vous n\'avez pas le droit de lire ce fichier.');
}

try {
	$tpl->assign('content', $file->render());
	$tpl->assign('file', $file);
	$tpl->display('common/files/_preview.tpl');
}
catch (\LogicException $e) {
	$file->serve($session);
}
