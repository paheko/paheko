<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$cats = new Compta\Categories;

if (null !== qg('depenses'))
    $type = Compta\Categories::DEPENSES;
else
    $type = Compta\Categories::RECETTES;

$tpl->assign('type', $type);
$tpl->assign('liste', $cats->getList($type));

$tpl->display('admin/compta/categories/index.tpl');
