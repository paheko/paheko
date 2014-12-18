<?php

namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$comptes = new Compta\Comptes;

?>