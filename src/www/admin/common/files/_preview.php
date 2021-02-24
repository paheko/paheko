<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Web\Render\Skriv;

require_once __DIR__ . '/../../_inc.php';

$file = Files::get(qg('p'));

if (!$file->checkReadAccess($session)) {
    throw new UserException('Vous n\'avez pas le droit de lire ce fichier.');
}

$content = Skriv::render($file, f('content'));

$tpl->assign('content', $content);

$tpl->assign('custom_css', ['!web/css.php']);

$tpl->display('common/files/_preview.tpl');
