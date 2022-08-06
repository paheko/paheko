<?php
namespace Garradin;

use Garradin\Entities\Search as SE;
use Garradin\Search;

require_once __DIR__ . '/_inc.php';

$query = trim((string) (qg('q') ?? f('q')));

$list = null;

// Recherche simple
if ($query !== '') {
	$list = Search::quick(SE::TARGET_USERS, $query);
}

$tpl->assign(compact('query', 'list'));

$tpl->display('users/selector.tpl');
