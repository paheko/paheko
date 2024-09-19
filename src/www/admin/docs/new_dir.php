<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$parent = Files::get(qg('p'));
$session = Session::getInstance();

if (!$parent || !$parent->canCreateDirHere($session)) {
	throw new UserException('Vous n\'avez pas le droit de créer de répertoire ici.', 403);
}

$csrf_key = 'create_dir';

$form->runIf('create', function () use ($parent, $session) {
	$dir = $parent->mkdir((string) f('name'), $session);

	$url = '!docs/?path=' . $dir->path;

	if (null !== qg('_dialog')) {
		Utils::reloadParentFrame(null === qg('no_redir') ? $url : null);
	}

	Utils::redirect($url);
}, $csrf_key);

$tpl->assign(compact('csrf_key'));

$tpl->display('docs/new_dir.tpl');
