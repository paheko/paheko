<?php

/*
 * Tests : vérification que les conditions pour s'exécuter sont remplies
 */

$tests = array(
    'Version de PHP installée inférieure à 5.3'
        =>  version_compare(phpversion(), '5.3', '<'),
    'Algorithme Blowfish de hashage de mot de passe non-présent'
        =>  !defined('CRYPT_BLOWFISH') || !CRYPT_BLOWFISH,
    'Module de bases de données SQLite3 n\'est pas installé'
        =>  !class_exists('SQLite3'),
    'Dummy' => true,
);

$fail = false;

foreach ($tests as $desc=>$fail)
{
    if ($fail)
    {
        echo $desc . "\n";
    }
}

if ($fail)
{
    echo "Erreur fatale : Garradin a besoin que la condition mentionnée soit remplie pour s'exécuter.\n";
    exit;
}

define('GARRADIN_ROOT', __DIR__ . '/..');
define('GARRADIN_DB_FILE', GARRADIN_ROOT . '/garradin_asso.db');
define('GARRADIN_DB_SCHEMA', GARRADIN_ROOT . '/DB_SCHEMA');

class Garradin_Exception extends Exception {};
class Garradin_Internal_Exception extends Garradin_Exception {};
class Garradin_User_Exception extends Garradin_Exception {};

?>