<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_categories.php';

$cats = new Garradin_Compta_Categories;

if (isset($_GET['depenses']))
    $type = Garradin_Compta_Categories::DEPENSES;
else
    $type = Garradin_Compta_Categories::RECETTES;

$tpl->assign('type', $type);
$tpl->assign('liste', $cats->getList($type));

$tpl->display('admin/compta/categories.tpl');

?>