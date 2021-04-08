<?php
namespace Garradin;

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$q = trim(f('q'));

$tpl->assign('query', $q);

if ($q) {
	$r = Web::search($q);
	$tpl->assign('results', $r);
	$tpl->assign('results_count', count($r));
}

function tpl_clean_snippet($str) {
	return preg_replace('!&lt;(/?b)&gt;!', '<$1>', $str);
}

$tpl->register_modifier('clean_snippet', 'Garradin\tpl_clean_snippet');

$tpl->display('web/search.tpl');
