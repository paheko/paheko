<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_comptes.php';
$comptes = new Garradin_Compta_Comptes;

?>