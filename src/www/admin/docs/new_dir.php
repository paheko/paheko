<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$parent = trim(qg('p'));

if (!File::checkCreateAccess(File::CONTEXT_DOCUMENTS, $session)) {
	throw new UserException('Vous n\'avez pas le droit de créer de répertoire ici.');
}

$csrf_key = 'create_dir';

$form->runIf('create', function () use ($parent) {
	$name = trim(f('name'));
	File::validatePath($parent . '/' . $name);
	File::createDirectory($parent, $name);
}, $csrf_key, '!docs/?p=' . $parent);

$tpl->assign(compact('csrf_key'));

$tpl->display('docs/new_dir.tpl');
