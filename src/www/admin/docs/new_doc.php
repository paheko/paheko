<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

if (!WOPI_DISCOVERY_URL) {
	throw new UserException('Le support des documents Office est désactivé.');
}

$ext = qg('ext');
$parent = qg('p');

if (!File::canCreate($parent)) {
	throw new UserException('Vous n\'avez pas le droit de créer de document ici.', 403);
}

$csrf_key = 'create_doc';

$form->runIf('create', function () use ($parent, $ext) {
	$name = trim((string) f('name'));
	$name = preg_replace('/\.\w+$/', '', $name);
	$file = Files::createDocument($parent, $name, $ext, Session::getInstance());
	Utils::redirect('!common/files/edit.php?p=' . rawurlencode($file->path));
}, $csrf_key);

if ($ext == 'ods') {
	$submit_name = 'Créer le tableau';
}
elseif ($ext == 'odp') {
	$submit_name = 'Créer la présentation';
}
else {
	$submit_name = 'Créer le document';
}

$tpl->assign(compact('csrf_key', 'submit_name', 'ext', 'parent'));

$tpl->display('docs/new_doc.tpl');
