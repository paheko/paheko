<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();
$parent = $_GET['p'] ?? '';

if (empty($parent)
	|| !File::canCreateDir($parent . '/example', $session)) {
	throw new UserException('Vous n\'avez pas le droit de créer de répertoire ici.', 403);
}

$csrf_key = 'create_dir';

$form->runIf('create', function () use ($session, $parent) {
	$path = $parent . '/' . ($_POST['name'] ?? '');

	if (!File::canCreateDir($parent . '/example', $session)) {
		throw new UserException('Vous n\'avez pas le droit de créer de répertoire ici.', 403);
	}

	// We actually have to use Files::mkdir here, as $parent->mkdir cannot
	// work if $parent path does not exist currently
	$dir = Files::mkdir($path);

	$url = '!docs/?path=' . $dir->path;

	if (null !== qg('_dialog')) {
		Utils::reloadParentFrame(null === qg('no_redir') ? $url : null);
	}

	Utils::redirect($url);
}, $csrf_key);

$tpl->assign(compact('csrf_key'));

$tpl->display('docs/new_dir.tpl');
