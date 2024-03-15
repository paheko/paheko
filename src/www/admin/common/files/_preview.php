<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Web\Render\Render;
use Paheko\Web\Web;

require_once __DIR__ . '/../../_inc.php';

$page = null;
$content = f('content');

if (null == $content) {
	throw new UserException('Aucun contenu à prévisualiser');
}

// Preview single markdown file in documents
if ($path = qg('f')) {
	$file = Files::get($path);

	if (!$file || !$file->canRead()) {
		throw new UserException('Vous n\'avez pas le droit de lire ce fichier.');
	}

	$file->preview();
	return;
}
// Preview single web page
elseif ($web = qg('w')) {
	$page = Web::get((int)$web);

	if (!$page || !($file = $page->dir()) || !$file->canRead()) {
		throw new UserException('Vous n\'avez pas le droit de lire ce fichier.');
	}

	$content = $page->preview($content);
}
else {
	throw new UserException('Fichier inconnu');
}

$tpl->assign(compact('file', 'content'));

$tpl->assign('custom_css', [BASE_URL . 'content.css']);

$tpl->display('common/files/_preview.tpl');
