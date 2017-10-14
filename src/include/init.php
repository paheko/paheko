<?php

namespace Garradin;

use KD2\ErrorManager;
use KD2\Security;
use KD2\Form;

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

    // Pour installations sans vhost
    $path = str_replace('/www', '', $path);

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
    'CACHE_ROOT'            => DATA_ROOT . '/cache',
    'DB_FILE'               => DATA_ROOT . '/association.sqlite',
    'DB_SCHEMA'             => ROOT . '/include/data/schema.sql',
    'PLUGINS_ROOT'          => DATA_ROOT . '/plugins',
    'PREFER_HTTPS'          => false,
    'ALLOW_MODIFIED_IMPORT' => true,
    'PLUGINS_SYSTEM'        => '',
    'SHOW_ERRORS'           => false,
    'MAIL_ERRORS'           => false,
    'USE_CRON'              => false,
    'ENABLE_XSENDFILE'      => false,
    'SMTP_HOST'             => false,
    'SMTP_USER'             => null,
    'SMTP_PASSWORD'         => null,
    'SMTP_PORT'             => 587,
    'SMTP_SECURITY'         => 'STARTTLS',
];

foreach ($default_config as $const => $value)
{
    $const = sprintf('Garradin\\%s', $const);

    if (!defined($const))
    {
        define($const, $value);
    }
}

const WEBSITE = 'http://garradin.eu/';
const PLUGINS_URL = 'https://garradin.eu/plugins/list.json';

const NTP_SERVER = 'fr.pool.ntp.org';

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

/**
 * Auto-chargement des dépendances
 */
class Loader
{
    /**
     * Liste des classes déjà chargées
     * @var array
     */
    static protected $loaded = [];

    /**
     * Inclure un fichier de classe depuis le nom de la classe
     * @param  string $classname
     * @return void
     */
    static public function load($classname)
    {
        $classname = ltrim($classname, '\\');

        if (array_key_exists($classname, self::$loaded))
        {
            return true;
        }
        
        // Plugins
        if (substr($classname, 0, 16) == 'Garradin\\Plugin\\')
        {
            $classname = substr($classname, 16);
            $plugin_name = substr($classname, 0, strpos($classname, '\\'));
            $filename = str_replace('\\', '/', substr($classname, strpos($classname, '\\')+1));
            
            $path = Plugin::getPath(strtolower($plugin_name)) . '/lib/' . $filename . '.php';
        }
        else
        {
            // PSR-0 autoload
            $filename = str_replace('\\', '/', $classname);
            $path = ROOT . '/include/lib/' . $filename . '.php';
        }
        
        if (!file_exists($path))
        {
            throw new \Exception('File '.$path.' doesn\'t exists');
        }

        self::$loaded[$classname] = true;

        require $path;
    }
}

\spl_autoload_register(['Garradin\Loader', 'load'], true);

/*
 * Gestion des erreurs et exceptions
 */

class UserException extends \LogicException
{
}

// activer le gestionnaire d'erreurs/exceptions
ErrorManager::enable(SHOW_ERRORS ? ErrorManager::DEVELOPMENT : ErrorManager::PRODUCTION);
ErrorManager::setLogFile(DATA_ROOT . '/error.log');

// activer l'envoi de mails si besoin est
if (MAIL_ERRORS)
{
    ErrorManager::setEmail(MAIL_ERRORS);
}

ErrorManager::setExtraDebugEnv([
    'Garradin version' => garradin_version(),
    'Garradin data root' => DATA_ROOT,
    ]);

ErrorManager::setProductionErrorTemplate('<!DOCTYPE html><html><head><title>Erreur interne</title>
    <style type="text/css">
    body {font-family: sans-serif; }
    code, p, h1 { max-width: 400px; margin: 1em auto; display: block; }
    code { text-align: right; color: #666; }
    a { color: blue; }
    </style></head><body><h1>Erreur interne</h1><p>Désolé mais le serveur a rencontré une erreur interne
    et ne peut répondre à votre requête. Merci de ré-essayer plus tard.</p>
    <p>Si vous suspectez un bug dans Garradin, vous pouvez suivre 
    <a href="http://dev.kd2.org/garradin/Rapporter+un+bug">ces instructions</a>
    pour le rapporter.</p>
    <if(email)><p>Un-e responsable a été notifié-e et cette erreur sera corrigée dès que possible.</p></if>
    <if(log)><code>Référence : <b>{$ref}</b></code></if>
    <p><a href="' . WWW_URL . '">&larr; Retour à la page d\'accueil</a></p>
    </body></html>');

ErrorManager::setHtmlFooter('<hr /><section><article>Cette erreur est peut-être un bug dans Garradin&nbsp;? En ce cas vous pouvez le rapporter en suivant <a href="http://dev.kd2.org/garradin/Rapporter+un+bug">ces instructions</a>.</section></article>');

function user_error($e)
{
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

// Message d'erreur simple pour les erreurs de l'utilisateur
ErrorManager::setCustomExceptionHandler('\Garradin\UserException', '\Garradin\user_error');
ErrorManager::setCustomExceptionHandler('\KD2\MiniSkelMarkupException', '\Garradin\user_error');

// Clé secrète utilisée pour chiffrer les tokens CSRF etc.
if (!defined('Garradin\SECRET_KEY'))
{
    $key = base64_encode(Security::random_bytes(64));
    Install::setLocalConfig('SECRET_KEY', $key);
    define('Garradin\SECRET_KEY', $key);
}

// Intégration du secret pour les tokens
Form::tokenSetSecret(SECRET_KEY);

// Fonctions utilitaires bien utiles d'avoir dans le namespace global de Garradin
function obj_has($obj, $pattern)
{
    return \KD2\Helpers::obj_has($obj, $pattern);
}

function obj_get($src, $pattern, $default = null)
{
    return \KD2\Helpers::obj_get($src, $pattern, $default);
}

/*
 * Vérifications pour enclencher le processus d'installation ou de mise à jour
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
