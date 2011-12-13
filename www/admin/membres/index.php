<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits'] < Garradin_Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

$cats = new Garradin_Membres_Categories;

$cat = (int) utils::get('cat') ?: 0;
$page = (int) utils::get('page') ?: 1;

$tpl->assign('membres_cats', $cats->listSimple());
$tpl->assign('current_cat', $cat);

$tpl->assign('liste', $membres->getList($cat, $page));

$tpl->display('admin/membres/index.tpl');

?>