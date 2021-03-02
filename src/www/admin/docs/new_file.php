<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$parent = trim(qg('p'));

if (!File::checkCreateAccess(File::CONTEXT_DOCUMENTS, $session)) {
	throw new UserException('Vous n\'avez pas le droit de créer de répertoire ici.');
}

$csrf_key = 'create_file';

$form->runIf('create', function () use ($parent) {
	$name = trim(f('name'));

	if (!strpos($name, '.')) {
		$name .= '.txt';
	}

	File::validatePath($parent . '/' . $name);

	$file = File::createAndStore($parent, $name, null, '');
	$file->set('mime', 'text/plain');
	$file->save();
}, $csrf_key, '!docs/?p=' . $parent);

$tpl->assign(compact('csrf_key'));

$tpl->display('docs/new_file.tpl');
