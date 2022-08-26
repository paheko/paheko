<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$parent = qg('path');

if (!File::checkCreateAccess($parent, $session)) {
	throw new UserException('Vous n\'avez pas le droit de créer de répertoire ici.');
}

$csrf_key = 'create_dir';

$form->runIf('create', function () use ($parent) {
	$name = trim((string) f('name'));
	$f = Files::mkdir($parent . '/' . $name);

	$url = '!docs/?path=' . $f->path;

	if (null !== qg('_dialog')) {
		Utils::reloadParentFrame($url);
	}

	Utils::redirect($url);
}, $csrf_key);

$tpl->assign(compact('csrf_key'));

$tpl->display('docs/new_dir.tpl');
