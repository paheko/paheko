<?php

namespace Garradin;
require_once __DIR__ . '/../_inc.php';

if ($user['droits']['wiki'] < Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$wiki = new Wiki;
$wiki->setRestrictionCategorie($user['id_categorie'], $user['droits']['wiki']);

?>