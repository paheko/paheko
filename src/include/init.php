<?php

namespace Garradin;

error_reporting(-1);

/*
 * Version de Garradin
 */

function garradin_version()
{
    if (defined('Garradin\VERSION'))
    {
        return VERSION;
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

    define('Garradin\VERSION', $version);
    return $version;
}

function garradin_manifest()
{
    $file = __DIR__ . '/../../manifest.uuid';

    if (@file_exists($file))
    {
        return substr(trim(file_get_contents($file)), 0, 10);
    }

    return false;
}

/*
 * Configuration globale
 */

// Configuration externalisée
if (file_exists(__DIR__ . '/../config.local.php'))
{
    require __DIR__ . '/../config.local.php';
}

// Configuration par défaut, si les constantes ne sont pas définies dans config.local.php
// (fallback)
if (!defined('Garradin\ROOT'))
{
    define('Garradin\ROOT', dirname(__DIR__));
}

if (!defined('Garradin\DATA_ROOT'))
{
    define('Garradin\DATA_ROOT', ROOT);
}

if (!defined('Garradin\WWW_URI'))
{
    // Automagic URL discover
    $path = str_replace(ROOT . '/www', '', getcwd());
    $path = str_replace($path, '', dirname($_SERVER['SCRIPT_NAME']));
    $path = (!empty($path[0]) && $path[0] != '/') ? '/' . $path : $path;
    $path = (substr($path, -1) != '/') ? $path . '/' : $path;
    define('Garradin\WWW_URI', $path);
}

if (!defined('Garradin\WWW_URL'))
{
    $host = isset($_SERVER['HTTP_HOST']) 
        ? $_SERVER['HTTP_HOST'] 
        : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
    define('Garradin\WWW_URL', 'http' . (!empty($_SERVER['HTTPS']) ? 's' : '') . '://' . $host . WWW_URI);
}

static $default_config = [
    'CACHE_ROOT'       => DATA_ROOT . '/cache',
    'DB_FILE'          => DATA_ROOT . '/association.sqlite',
    'DB_SCHEMA'        => ROOT . '/include/data/schema.sql',
    'PLUGINS_ROOT'     => DATA_ROOT . '/plugins',
    'PREFER_HTTPS'     => false,
    'PLUGINS_SYSTEM'   => '',
    'SHOW_ERRORS'      => true,
    'MAIL_ERRORS'      => false,
    'USE_CRON'         => false,
    'ENABLE_XSENDFILE' => false,
    'SMTP_HOST'        => false,
    'SMTP_USER'        => null,
    'SMTP_PASSWORD'    => null,
    'SMTP_PORT'        => 587,
    'SMTP_SECURITY'    => 'STARTTLS',
];

foreach ($default_config as $const => $value)
{
    $const = sprintf('Garradin\\%s', $const);

    if (!defined($const))
    {
        define($const, $value);
    }
}

define('Garradin\WEBSITE', 'http://garradin.eu/');
define('Garradin\PLUGINS_URL', 'https://garradin.eu/plugins/list.json');

// PHP devrait être assez intelligent pour chopper la TZ système mais nan
// il sait pas faire (sauf sur Debian qui a le bon patch pour ça), donc pour 
// éviter le message d'erreur à la con on définit une timezone par défaut
// Pour utiliser une autre timezone, il suffit de définir date.timezone dans
// un .htaccess ou dans config.local.php
if (!ini_get('date.timezone'))
{
    if (($tz = @date_default_timezone_get()) && $tz != 'UTC')
    {
        ini_set('date.timezone', $tz);
    }
    else
    {
        ini_set('date.timezone', 'Europe/Paris');
    }
}

ini_set('error_log', DATA_ROOT . '/error.log');
ini_set('log_errors', true);

if (SHOW_ERRORS)
{
    // Gestion par défaut des erreurs
    ini_set('display_errors', true);
    ini_set('html_errors', false);

    if (PHP_SAPI != 'cli')
    {
        ini_set('error_prepend_string', '<!DOCTYPE html><meta charset="utf-8" /><style type="text/css">body { font-family: sans-serif; } h3 { color: darkred; } 
            pre { text-shadow: 2px 2px 5px black; color: darkgreen; font-size: 2em; float: left; margin: 0 1em 0 0; padding: 1em; background: #cfc; border-radius: 50px; }</style>
            <pre> \__/<br /> (xx)<br />//||\\\\</pre>
            <h1>Erreur fatale</h1>
            <p>Une erreur fatale s\'est produite à l\'exécution de Garradin. Pour rapporter ce bug
            merci d\'inclure le message ci-dessous :</p>
            <h3>');
        ini_set('error_append_string', '</h3><hr />
            <p><a href="http://dev.kd2.org/garradin/Rapporter%20un%20bug">Comment rapporter un bug</a></p>');
    }
}

/*
 * Gestion des erreurs et exceptions
 */

class UserException extends \LogicException
{
}

function exception_error_handler($errno, $errstr, $errfile, $errline )
{
    // For @ ignored errors
    if (error_reporting() === 0) return;
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function exception_handler($e)
{
    if ($e instanceOf UserException || $e instanceOf \KD2\MiniSkelMarkupException)
    {
        try {
            if (PHP_SAPI == 'cli')
            {
                echo $e->getMessage();
            }
            else
            {
                $tpl = Template::getInstance();

                $tpl->assign('error', $e->getMessage());
                $tpl->display('error.tpl');
            }

            exit;
        }
        catch (Exception $e)
        {
        }
    }

    $file = str_replace(ROOT, '', $e->getFile());

    $error = "Exception of type ".get_class($e)." happened !\n\n".
        $e->getCode()." - ".$e->getMessage()."\n\nIn: ".
        $file . ":" . $e->getLine()."\n\n";

    if (!empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['REQUEST_URI']))
        $error .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n\n";

    $error .= $e->getTraceAsString();
    $error .= "\n-------------\n";
    $error .= 'Garradin version: ' . garradin_version() . "\n";
    $error .= 'Garradin manifest: ' . garradin_manifest() . "\n";
    $error .= 'PHP version: ' . phpversion() . "\n";
    $error .= 'Garradin data root: ' . \Garradin\DATA_ROOT . "\n";

    foreach ($_SERVER as $key=>$value)
    {
        if (is_array($value))
            $value = json_encode($value);

        $error .= $key . ': ' . $value . "\n";
    }
    
    $error = str_replace("\r", '', $error);
    error_log($error);
    
    if (MAIL_ERRORS)
    {
        mail(MAIL_ERRORS, '[Garradin] Erreur d\'exécution', $error, 'From: "' . WWW_URL . '" <noreply@no.reply>');
    }

    if (PHP_SAPI == 'cli')
    {
        echo $error;
    }
    else
    {
        echo '<!DOCTYPE html><meta charset="utf-8" /><style type="text/css">body { font-family: sans-serif; } h3 { color: darkred; }
        pre { text-shadow: 2px 2px 5px black; color: darkgreen; font-size: 2em; float: left; margin: 0 1em 0 0; padding: 1em; background: #cfc; border-radius: 50px; }</style>
        <pre> \__/<br /> (xx)<br />//||\\\\</pre>
        <h1>Erreur d\'exécution</h1>';

        if (SHOW_ERRORS)
        {
            echo '<p>Une erreur s\'est produite à l\'exécution de Garradin. Pour rapporter ce bug
            merci d\'inclure le message suivant :</p>
            <textarea cols="70" rows="'.substr_count($error, "\n").'">'.htmlspecialchars($error, ENT_QUOTES, 'UTF-8').'</textarea>
            <hr />
            <p><a href="http://dev.kd2.org/garradin/Rapporter%20un%20bug">Comment rapporter un bug</a></p>';
        }
        else
        {
            echo '<p>Une erreur s\'est produite à l\'exécution de Garradin.</p>
            <p>Le webmaster a été prévenu.</p>';
        }
    }

    exit;
}

set_error_handler('Garradin\exception_error_handler');
set_exception_handler('Garradin\exception_handler');

/**
 * Auto-load classes and libs
 */
class Loader
{
    /**
     * Already loaded filenames
     * @var array
     */
    static protected $loaded = [];

    /**
     * Loads a class from the $name
     * @param  stringg $classname
     * @return bool true
     */
    static public function load($classname)
    {
        $classname = ltrim($classname, '\\');
        
        if (substr($classname, 0, 16) == 'Garradin\\Plugin\\')
        {
            $classname = substr($classname, 16);
            $plugin_name = substr($classname, 0, strpos($classname, '\\'));
            $filename = str_replace('\\', '/', substr($classname, strpos($classname, '\\')+1));
            
            $path = 'phar://' . PLUGINS_ROOT . '/' . strtolower($plugin_name) . '.tar.gz/lib/' . $filename . '.php';
        }
        else
        {
            $filename = str_replace('\\', '/', $classname);
            $path = ROOT . '/include/lib/' . $filename . '.php';
        }

        if (array_key_exists($path, self::$loaded))
        {
            return true;
        }

        if (!file_exists($path)) {
            throw new \Exception('File '.$path.' doesn\'t exists');
        }

        self::$loaded[$path] = true;

        require $path;
    }
}

\spl_autoload_register(['Garradin\Loader', 'load'], true);

$n = new Membres;

/*
 * Inclusion des fichiers de base
 */

if (!defined('Garradin\INSTALL_PROCESS') && !defined('Garradin\UPGRADE_PROCESS'))
{
    if (!file_exists(DB_FILE))
    {
        Utils::redirect('/admin/install.php');
    }

    $config = Config::getInstance();

    if (version_compare($config->getVersion(), garradin_version(), '<'))
    {
        Utils::redirect('/admin/upgrade.php');
    }
}
