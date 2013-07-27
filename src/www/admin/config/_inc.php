<?php

namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['config'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

?>