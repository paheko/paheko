<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$classe = (int) qg('classe');

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

$tpl->register_modifier('get_position', 'Garradin\tpl_get_position');

$tpl->display('admin/compta/comptes/index.tpl');
