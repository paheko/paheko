<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Web\Render\Skriv;
use Garradin\Web\Web;

require_once __DIR__ . '/../../_inc.php';

if ($path = qg('p')) {
	$file = Files::get($path);

	if (!$file || !$file->checkReadAccess($session)) {
		throw new UserException('Vous n\'avez pas le droit de lire ce fichier.');
	}
}
elseif ($web = qg('w')) {
	$page = Web::get($web);

	if (!$page || !$page->file() || !$page->file()->checkReadAccess($session)) {
		throw new UserException('Vous n\'avez pas le droit de lire ce fichier.');
	}

	$file = $page->file();
}

$content = Skriv::render($file, f('content'));

$tpl->assign(compact('file', 'content'));

$tpl->assign('custom_css', ['!web/css.php']);

$tpl->display('common/files/_preview.tpl');
