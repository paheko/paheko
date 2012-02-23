<?php

require_once __DIR__ . '/_inc.php';

$page = (int) utils::get('p') ?: 1;

$tpl->assign('page', $page);
$tpl->assign('bypage', Garradin_Wiki::ITEMS_PER_PAGE);
$tpl->assign('total', $wiki->countRecentModifications());
$tpl->assign('list', $wiki->listRecentModifications($page));

$tpl->display('admin/wiki/recent.tpl');

?>