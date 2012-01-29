<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Garradin_Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

$cats = new Garradin_Membres_Categories;

$cat = (int) utils::get('cat') ?: 0;
$page = (int) utils::get('page') ?: 1;

$search_field = utils::get('search_field') ?: '';
$search_query = utils::get('search_query') ?: '';

if ($search_field && $search_query)
{
    $tpl->assign('liste', $membres->search($search_field, $search_query));
}
else
{
    $tpl->assign('liste', $membres->getList($cat, $page));
}

$tpl->assign('membres_cats', $cats->listSimple());
$tpl->assign('current_cat', $cat);

$tpl->assign('page', $page);

$tpl->assign('search_field', $search_field);
$tpl->assign('search_query', $search_query);

$tpl->display('admin/membres/index.tpl');

?>