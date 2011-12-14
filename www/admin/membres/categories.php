<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

$cats = new Garradin_Membres_Categories;

$tpl->assign('liste', $cats->listComplete());

$tpl->display('admin/membres/categories.tpl');

?>