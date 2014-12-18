<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$cats = new Compta\Categories;

if (isset($_GET['depenses']))
    $type = Compta\Categories::DEPENSES;
else
    $type = Compta\Categories::RECETTES;

$tpl->assign('type', $type);
$tpl->assign('liste', $cats->getList($type));

$tpl->display('admin/compta/categories/index.tpl');

?>