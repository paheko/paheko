<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['wiki'] < Garradin_Membres::DROIT_ACCES)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.wiki.php';
$wiki = new Garradin_Wiki;
$wiki->setRestrictionCategorie($user['id_categorie'], $user['droits']['wiki']);

?>