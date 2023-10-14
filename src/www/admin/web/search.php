<?php
namespace Paheko;

use Paheko\Web\Web;
use Paheko\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$q = trim((string) f('q'));

$tpl->assign('query', $q);

if ($q) {
	$r = Web::search($q);
	$tpl->assign('results', $r);
	$tpl->assign('results_count', count($r));
}

$tpl->display('web/search.tpl');
