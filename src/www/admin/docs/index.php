<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$parent = trim(qg('p')) ?: null;
$context = qg('c') ?: File::CONTEXT_DOCUMENTS;

$files = Files::list($context, $parent);

// We consider that the first file has the same rights as the others
if (count($files)) {
	$can_delete = current($files)->checkDeleteAccess($session);
	$can_write = current($files)->checkWriteAccess($session);
}
else {
	$can_delete = $can_write = false;
}

$tpl->assign(compact('context', 'parent', 'files', 'can_write', 'can_delete'));

$tpl->display('docs/index.tpl');
