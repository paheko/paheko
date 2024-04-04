<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;

use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$parent = Files::getByHashID(qg('id'));
$default_ext = qg('ext') ?? 'md';

if (!$parent->canCreateHere(Session::getInstance())) {
	throw new UserException('Vous n\'avez pas le droit de créer de fichier ici.');
}

$csrf_key = 'create_file';

$form->runIf('create', function () use ($parent, $default_ext) {
	$name = trim((string) f('name'));

	if ($default_ext && !strpos($name, '.')) {
		$name .= '.' . $default_ext;
	}

	$target = $parent->path . '/' . $name;

	if (Files::exists($target)) {
		throw new UserException('Un fichier existe déjà avec ce nom : ' . $name);
	}

	$file = Files::createFromString($target, '');

	Utils::redirect('!common/files/edit.php?fallback=code&p=' . rawurlencode($file->path));
}, $csrf_key);

$tpl->assign(compact('csrf_key', 'parent'));

$tpl->display('docs/new_file.tpl');
