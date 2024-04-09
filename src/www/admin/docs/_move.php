<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

if (empty($check) || !is_array($check)) {
	throw new UserException('Invalid call', 400);
}

$csrf_key = 'move_files';

$form->runIf('move', function () use ($check) {
	$target = f('move');
	$files = [];

	foreach ($check as $file) {
		$file = Files::get($file);

		if (!$file) {
			continue;
		}

		if (!$file->canMoveTo($target)) {
			throw new UserException(sprintf('Vous n\'avez pas le droit de déplacer le fichier "%s" dans "%s"', $file->path, $target));
		}

		$files[] = $file;
	}

	unset($file);

	foreach ($files as $file) {
		$file->move($target);
	}

	Utils::redirectDialog('!docs/?path=' . rawurlencode($target));
}, $csrf_key);

$current = f('current') ?? f('parent');

if (!$current) {
	$first_file = Files::get(current($check));

	if (!$first_file) {
		throw new UserException('Fichier introuvable');
	}

	$current = $first_file->parent;
}

$directories = Files::list($current);
$directories = array_filter($directories, function (File $file) {
	return $file->type == File::TYPE_DIRECTORY;
});

$breadcrumbs = Files::getBreadcrumbs($current);
$parent = Utils::dirname($current);
$current_path = $current;
$current_path_name = Utils::basename($current);

$count = count($check);

$tpl->assign(compact('check', 'directories', 'breadcrumbs', 'parent', 'current_path', 'current_path_name', 'count', 'csrf_key'));

$tpl->display('docs/move.tpl');
