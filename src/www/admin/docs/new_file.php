<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$parent = qg('path');

if (!File::canCreate($parent)) {
	throw new UserException('Vous n\'avez pas le droit de créer de fichier ici.');
}

$csrf_key = 'create_file';

$form->runIf('create', function () use ($parent) {
	$name = trim((string) f('name'));

	if (!strpos($name, '.')) {
		$name .= '.md';
	}

	$target = $parent . '/' . $name;

	if (Files::exists($target)) {
		throw new UserException('Un fichier existe déjà avec ce nom : ' . $name);
	}

	$file = Files::createFromString($target, '');

	Utils::redirect('!common/files/edit.php?fallback=code&p=' . rawurlencode($file->path));
}, $csrf_key);

$tpl->assign(compact('csrf_key'));

$tpl->display('docs/new_file.tpl');
