<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Garradin_Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

$cats = new Garradin_Membres_Categories;

$cat = (int) utils::get('cat') ?: 0;
$page = (int) utils::get('p') ?: 1;

$search_field = utils::get('search_field') ?: '';
$search_query = utils::get('search_query') ?: '';

if ($search_field && $search_query)
{
    $tpl->assign('liste', $membres->search($search_field, $search_query));
    $tpl->assign('total', -1);
}
else
{
    $tpl->assign('liste', $membres->listByCategory($cat, $page));
    $tpl->assign('total', $membres->countByCategory($cat));
}

$tpl->assign('membres_cats', $cats->listSimple());
$tpl->assign('current_cat', $cat);

$tpl->assign('page', $page);
$tpl->assign('bypage', Garradin_Membres::ITEMS_PER_PAGE);

$tpl->assign('search_field', $search_field);
$tpl->assign('search_query', $search_query);

$tpl->display('admin/membres/index.tpl');

?>