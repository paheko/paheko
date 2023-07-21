<?php
namespace Paheko;

use Paheko\Files\Files;
use Paheko\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$q = trim((string) f('q'));

$tpl->assign('query', $q);

if ($q) {
	$r = Files::search($q, File::CONTEXT_DOCUMENTS . '%');
	$tpl->assign('results', $r);
	$tpl->assign('results_count', count($r));
}

$tpl->display('docs/search.tpl');
