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
    #'Dummy' => true,
);

$fail = false;

if (PHP_SAPI != 'cli' && array_sum($tests) > 0)
    echo '<pre>';

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

    if (PHP_SAPI != 'cli')
        echo '</pre>';

    exit;
}

/*
 * Configuration globale
 */

define('GARRADIN_ROOT', dirname(__DIR__));
define('GARRADIN_DB_FILE', GARRADIN_ROOT . '/association.db');
define('GARRADIN_DB_SCHEMA', GARRADIN_ROOT . '/DB_SCHEMA');

// Automagic URL discover
$path = substr(__DIR__ . '/www', strlen($_SERVER['DOCUMENT_ROOT']));
$path = (!empty($path[0]) && $path[0] != '/') ? '/' . $path : $path;
$path = (substr($path, -1) != '/') ? $path . '/' : $path;
define('LOCAL_URL', 'http' . (!empty($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $path);

/*
 * Gestion des erreurs et exceptions
 */

class UserException extends LogicException {};

error_reporting(E_ALL);

function exception_error_handler($errno, $errstr, $errfile, $errline )
{
    // For @ ignored errors
    if (error_reporting() === 0) return;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function exception_handler($e)
{
    if ($e instanceOf UserException)
    {
        echo '<h3>'.$e->getMessage().'</h3>';
        exit;
    }

    $error = "Error happened !\n\n".
        $e->getCode()." - ".$e->getMessage()."\n\nIn: ".
        $e->getFile() . ":" . $e->getLine()."\n\n";

    if (!empty($_SERVER['HTTP_HOST']))
        $error .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n\n";

    $error .= $e->getTraceAsString();
    //$error .= print_r($_SERVER, true);

    echo $error;
    exit;
}

set_error_handler("exception_error_handler");
set_exception_handler("exception_handler");

require_once GARRADIN_ROOT . '/include/class.db.php';
require_once GARRADIN_ROOT . '/include/class.config.php';


?>