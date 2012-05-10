<?php

require_once __DIR__ . '/_inc.php';

if (utils::get('current') && !is_numeric(utils::get('current')))
{
    throw new UserException('Numéro de page parent invalide.');
}

$current = (int) utils::get('current');

$tpl->assign('current', $current);
$tpl->assign('list', $wiki->listBackParentTree($current));

$tpl->display('admin/wiki/_chercher_parent.tpl');

?>