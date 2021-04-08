<?php
namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$q = trim(f('q'));

$tpl->assign('query', $q);

if ($q) {
	$r = Files::search($q, File::CONTEXT_DOCUMENTS . '%');
	$tpl->assign('results', $r);
	$tpl->assign('results_count', count($r));
}

function tpl_clean_snippet($str) {
	return preg_replace('!&lt;(/?b)&gt;!', '<$1>', $str);
}

$tpl->register_modifier('clean_snippet', 'Garradin\tpl_clean_snippet');

$tpl->display('docs/search.tpl');
