<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$classe = (int) utils::get('classe');

$tpl->assign('classe', $classe);

if (!$classe)
{
    $tpl->assign('classes', $comptes->listTree(0, false));
}
else
{
    $positions = $comptes->getPositions();

    $tpl->assign('classe_compte', $comptes->get($classe));
    $tpl->assign('liste', $comptes->listTree($classe));
}

function tpl_get_position($pos)
{
    global $positions;
    return $positions[$pos];
}

$tpl->register_modifier('get_position', 'tpl_get_position');

$tpl->display('admin/compta/comptes.tpl');

?>