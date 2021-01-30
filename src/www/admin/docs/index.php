<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$parent = trim(qg('p')) ?: null;
$context = qg('c') ?: File::CONTEXT_DOCUMENTS;

$files = Files::list($context, $parent);

$can_delete = $session->canAccess($session::SECTION_DOCUMENTS, $session::ACCESS_ADMIN);

$tpl->assign(compact('context', 'parent', 'files', 'can_delete'));

$tpl->display('docs/index.tpl');
