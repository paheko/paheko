<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Garradin_Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

$cats = new Garradin_Membres_Categories;
$membres_cats = $cats->listSimple();
$membres_cats_cachees = $cats->listHidden();

$cat = (int) utils::get('cat') ?: 0;
$page = (int) utils::get('p') ?: 1;

$search_field = utils::get('search_field') ?: $membres->sessionGet('membre_search_field');
$search_query = utils::get('search_query') ?: '';

if ($search_field && $search_query)
{
    $membres->sessionStore('membre_search_field', $search_field);
    $tpl->assign('liste', $membres->search($search_field, $search_query));
    $tpl->assign('total', -1);
}
else
{
    if (!$cat)
    {
        $cat_id = array_diff(array_keys($membres_cats), array_keys($membres_cats_cachees));
    }
    else
    {
        $cat_id = (int) $cat;
    }

    $order = 'nom';
    $desc = false;

    if (utils::get('o'))
        $order = utils::get('o');

    if (isset($_GET['d']))
        $desc = true;

    $tpl->assign('order', $order);
    $tpl->assign('desc', $desc);

    $tpl->assign('liste', $membres->listByCategory($cat_id, $page, $order, $desc));
    $tpl->assign('total', $membres->countByCategory($cat_id));
}

$tpl->assign('membres_cats', $membres_cats);
$tpl->assign('membres_cats_cachees', $membres_cats_cachees);
$tpl->assign('current_cat', $cat);

$tpl->assign('page', $page);
$tpl->assign('bypage', Garradin_Membres::ITEMS_PER_PAGE);

$tpl->assign('search_field', $search_field);
$tpl->assign('search_query', $search_query);

$tpl->display('admin/membres/index.tpl');

?>