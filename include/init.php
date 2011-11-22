<?php

/*
 * Configuration globale
 */

define('GARRADIN_ROOT', dirname(__DIR__));
define('GARRADIN_DB_FILE', GARRADIN_ROOT . '/association.db');
define('GARRADIN_DB_SCHEMA', GARRADIN_ROOT . '/DB_SCHEMA');

// Automagic URL discover
$path = substr(GARRADIN_ROOT . '/www', strlen($_SERVER['DOCUMENT_ROOT']));
$path = (!empty($path[0]) && $path[0] != '/') ? '/' . $path : $path;
$path = (substr($path, -1) != '/') ? $path . '/' : $path;
define('WWW_URL', 'http' . (!empty($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $path);

/*
 * Gestion des erreurs et exceptions
 */

class UserException extends LogicException
{
}

error_reporting(-1);

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

require_once GARRADIN_ROOT . '/include/lib.utils.php';

if (!defined('GARRADIN_INSTALL_PROCESS'))
{
    require_once GARRADIN_ROOT . '/include/class.db.php';
    require_once GARRADIN_ROOT . '/include/class.config.php';
}

?>