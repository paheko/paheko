<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$cats = new Membres_Categories;

$membres_cats = $cats->listSimple();
$membres_cats_cachees = $cats->listHidden();

$cat = (int) utils::get('cat') ?: 0;
$page = (int) utils::get('p') ?: 1;

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

$fields = $config->get('champs_membres')->getListedFields();

$tpl->assign('champs', $fields);

$tpl->assign('liste', $membres->listByCategory($cat_id, array_keys($fields), $page, $order, $desc));
$tpl->assign('total', $membres->countByCategory($cat_id));

$tpl->assign('pagination_url', utils::getSelfUrl(true) . '?p=[ID]&amp;o=' . $order . ($desc ? '&amp;d' : ''));

$tpl->assign('membres_cats', $membres_cats);
$tpl->assign('membres_cats_cachees', $membres_cats_cachees);
$tpl->assign('current_cat', $cat);

$tpl->assign('page', $page);
$tpl->assign('bypage', Membres::ITEMS_PER_PAGE);

$tpl->display('admin/membres/index.tpl');

?>