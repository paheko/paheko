<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_exercices.php';

$e = new Garradin_Compta_Exercices;

$tpl->assign('liste', $e->getList());
$tpl->assign('current', $e->getCurrent());

$tpl->display('admin/compta/exercices.tpl');

?>