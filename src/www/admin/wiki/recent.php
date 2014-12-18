<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$page = (int) Utils::get('p') ?: 1;

$tpl->assign('page', $page);
$tpl->assign('bypage', Wiki::ITEMS_PER_PAGE);
$tpl->assign('total', $wiki->countRecentModifications());
$tpl->assign('list', $wiki->listRecentModifications($page));

$tpl->display('admin/wiki/recent.tpl');

?>