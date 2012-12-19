<?php

namespace Garradin;

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
    define('GARRADIN_DB_SCHEMA', GARRADIN_ROOT . '/include/data/schema.sql');
}

if (!defined('WWW_URI'))
{
    // Automagic URL discover
    $path = str_replace(GARRADIN_ROOT . '/www', '', getcwd());
    $path = str_replace($path, '', dirname($_SERVER['SCRIPT_NAME']));
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

class UserException extends \LogicException
{
}

error_reporting(-1);

function exception_error_handler($errno, $errstr, $errfile, $errline )
{
    // For @ ignored errors
    if (error_reporting() === 0) return;
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function exception_handler($e)
{
    if ($e instanceOf UserException || $e instanceOf miniSkelMarkupException)
    {
        try {
            $tpl = Template::getInstance();

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

set_error_handler("Garradin\exception_error_handler");
set_exception_handler("Garradin\exception_handler");

// Nettoyage des variables GPC pour ceux qui n'auraient
// toujours pas désactivé les magic quotes
if (get_magic_quotes_gpc())
{
    function strip_slashes_from_user_data(&$array)
    {
        foreach($array as $k => $v)
        {
            if (is_array($v))
            {
                strip_slashes_from_user_data($array[$k]);
                continue;
            }
            $array[$k] = stripslashes($v);
        }
    }

    strip_slashes_from_user_data($_GET);
    strip_slashes_from_user_data($_POST);
    strip_slashes_from_user_data($_COOKIE);
}

/**
 * Auto-load classes and libs
 */
class Loader
{
    /**
     * Already loaded filenames
     * @var array
     */
    static protected $loaded = array();

    static protected $libs = array(
        'utils',
        'squelette_filtres',
        'static_cache',
        );

    /**
     * Loads a class from the $name
     * @param  stringg $classname
     * @return bool true
     */
    static public function load($classname)
    {
        $classname = ltrim($classname, '\\');
        $filename  = '';
        $namespace = '';

        if ($lastnspos = strripos($classname, '\\')) 
        {
            $namespace = substr($classname, 0, $lastnspos);
            $classname = substr($classname, $lastnspos + 1);

            if ($namespace != 'Garradin')
            {
                $filename  = str_replace('\\', '/', $namespace) . '/';
            }
        }

        $classname = strtolower($classname);

        if (in_array($classname, self::$libs)) {
            $filename = 'lib.' . $classname . '.php';
        } else {
            $filename .= 'class.' . $classname . '.php';
        }

        $filename = GARRADIN_ROOT . '/include/' . $filename;

        if (array_key_exists($filename, self::$loaded))
        {
            return true;
        }

        if (!file_exists($filename)) {
            throw new \Exception('File '.$filename.' doesn\'t exists');
        }

        self::$loaded[$filename] = true;

        require $filename;
    }
}

\spl_autoload_register(array('Garradin\Loader', 'load'), true);

$n = new Membres;

/*
 * Inclusion des fichiers de base
 */

if (!defined('GARRADIN_INSTALL_PROCESS') && !defined('GARRADIN_UPGRADE_PROCESS'))
{
    if (!file_exists(GARRADIN_DB_FILE))
    {
        utils::redirect('/admin/install.php');
    }

    $config = Config::getInstance();

    if (version_compare($config->getVersion(), garradin_version(), '<'))
    {
        utils::redirect('/admin/upgrade.php');
    }
}

?>