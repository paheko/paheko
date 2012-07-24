<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_comptes_bancaires.php';
$banque = new Garradin_Compta_Comptes_Bancaires;

$tpl->assign('liste', $banque->getList());

$tpl->display('admin/compta/banques.tpl');

?>