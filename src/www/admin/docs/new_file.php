<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$parent = qg('path');

if (!File::checkCreateAccess($parent, $session)) {
	throw new UserException('Vous n\'avez pas le droit de créer de répertoire ici.');
}

$csrf_key = 'create_file';

$form->runIf('create', function () use ($parent) {
	$name = trim((string) f('name'));

	if (!strpos($name, '.')) {
		$name .= '.skriv';
	}

	$file = Files::createFromString($parent . '/' . $name, '');

	Utils::redirect('!common/files/edit.php?p=' . rawurlencode($file->path));
}, $csrf_key);

$tpl->assign(compact('csrf_key'));

$tpl->display('docs/new_file.tpl');
