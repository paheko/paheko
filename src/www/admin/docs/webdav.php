<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$dir = Files::get(File::CONTEXT_DOCUMENTS);

$tpl->assign(compact('dir'));

$tpl->display('docs/webdav.tpl');
