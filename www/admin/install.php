<?php

/*
 * Tests : vérification que les conditions pour s'exécuter sont remplies
 */

$tests = array(
    'La version de PHP installée est inférieure à 5.3 !'
        =>  version_compare(phpversion(), '5.3', '<'),
    'L\'algorithme Blowfish de hashage de mot de passe n\'est pas présent !'
        =>  !defined('CRYPT_BLOWFISH') || !CRYPT_BLOWFISH,
    'Le module de bases de données SQLite3 n\'est pas installé !'
        =>  !class_exists('SQLite3'),
    'La librairie Template_Lite ne semble pas disponible !'
        =>  !file_exists(__DIR__ . '/../../include/template_lite/class.template.php'),
    #'Dummy' => true,
);

$fail = false;

if (PHP_SAPI != 'cli' && array_sum($tests) > 0)
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre>';
}

foreach ($tests as $desc=>$fail)
{
    if ($fail)
    {
        echo $desc . "\n";
    }
}

if ($fail)
{
    echo "\n<b>Erreur fatale :</b> Garradin a besoin que la condition mentionnée soit remplie pour s'exécuter.\n";

    if (PHP_SAPI != 'cli')
        echo '</pre>';

    exit;
}

define('GARRADIN_INSTALL_PROCESS', true);

require __DIR__ . '/../../include/init.php';


?>