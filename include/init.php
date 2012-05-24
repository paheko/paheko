<?php

/*
 * Version de Garradin
 */

function garradin_version()
{
    if (defined('GARRADIN_VERSION'))
    {
        return GARRADIN_VERSION;
    }

    $file = __DIR__ . '/../VERSION';

    if (file_exists($file))
    {
        $version = trim(file_get_contents($file));
    }
    else
    {
        $version = 'unknown';
    }

    define('GARRADIN_VERSION', $version);
    return $version;
}

function garradin_manifest()
{
    $file = __DIR__ . '/../manifest.uuid';

    if (file_exists($file))
    {
        return substr(trim(file_get_contents($file)), 0, 10);
    }

    return false;
}

/*
 * Configuration globale
 */

// Configuration externalisée, pour projets futurs (fermes de garradins ?)
if (file_exists(__DIR__ . '/../config.local.php'))
{
    require __DIR__ . '/../config.local.php';
}

if (!defined('GARRADIN_ROOT'))
{
    define('GARRADIN_ROOT', dirname(__DIR__));
}

if (!defined('GARRADIN_DB_FILE'))
{
    define('GARRADIN_DB_FILE', GARRADIN_ROOT . '/association.db');
}

if (!defined('GARRADIN_DB_SCHEMA'))
{
    define('GARRADIN_DB_SCHEMA', GARRADIN_ROOT . '/DB_SCHEMA');
}

if (!defined('WWW_URI'))
{
    // Automagic URL discover
    $path = substr(GARRADIN_ROOT . '/www', strlen($_SERVER['DOCUMENT_ROOT']));
    $path = (!empty($path[0]) && $path[0] != '/') ? '/' . $path : $path;
    $path = (substr($path, -1) != '/') ? $path . '/' : $path;
    define('WWW_URI', $path);
}

if (!defined('WWW_URL'))
{
    define('WWW_URL', 'http' . (!empty($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . WWW_URI);
}

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
    if ($e instanceOf UserException || $e instanceOf miniSkelMarkupException)
    {
        try {
            require_once GARRADIN_ROOT . '/include/template.php';
            $tpl = Garradin_TPL::getInstance();

            $tpl->assign('error', $e->getMessage());
            $tpl->display('error.tpl');
            exit;
        }
        catch (Exception $e)
        {
        }
    }

    $error = "Error happened !\n\n".
        $e->getCode()." - ".$e->getMessage()."\n\nIn: ".
        $e->getFile() . ":" . $e->getLine()."\n\n";

    if (!empty($_SERVER['HTTP_HOST']))
        $error .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n\n";

    $error .= $e->getTraceAsString();
    //$error .= print_r($_SERVER, true);

    echo '<pre>';
    echo $error;
    exit;
}

set_error_handler("exception_error_handler");
set_exception_handler("exception_handler");

/*
 * Inclusion des fichiers de base
 */

require_once GARRADIN_ROOT . '/include/lib.utils.php';

if (!defined('GARRADIN_INSTALL_PROCESS') && !defined('GARRADIN_UPGRADE_PROCESS'))
{
    if (!file_exists(GARRADIN_DB_FILE))
    {
        utils::redirect('/admin/install.php');
    }

    require_once GARRADIN_ROOT . '/include/class.db.php';
    require_once GARRADIN_ROOT . '/include/class.config.php';
    $config = Garradin_Config::getInstance();

    if (version_compare($config->getVersion(), garradin_version(), '<'))
    {
        utils::redirect('/admin/upgrade.php');
    }
}

?>