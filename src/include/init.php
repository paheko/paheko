<?php

namespace Paheko;

use KD2\ErrorManager;
use KD2\Security;
use KD2\Form;
use KD2\Translate;
use KD2\DB\EntityManager;

error_reporting(-1);

const CONFIG_FILE = 'config.local.php';

/*
 * Version de Paheko
 */
function paheko_version()
{
	if (defined('Paheko\VERSION'))
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

	define('Paheko\VERSION', $version);
	return $version;
}

function paheko_manifest()
{
	$file = __DIR__ . '/../../manifest.uuid';

	if (@file_exists($file))
	{
		return substr(trim(file_get_contents($file)), 0, 10);
	}

	return false;
}

/**
 * Le code de Paheko ne s'écrit pas tout seul comme par magie,
 * merci de soutenir notre travail en faisant une contribution :)
 */
function paheko_contributor_license(): ?int
{
	static $level = null;

	if (null !== $level) {
		return $level;
	}

	if (!CONTRIBUTOR_LICENSE) {
		return null;
	}

	if (is_int(CONTRIBUTOR_LICENSE)) {
		return CONTRIBUTOR_LICENSE;
	}

	$key = CONTRIBUTOR_LICENSE;
	$key = gzinflate(base64_decode($key));
	list($email, $level, $hash) = explode('==', $key);
	$level = (int)hexdec($level);

	if (substr(sha1($email . $level), 0, 10) != $hash) {
		return null;
	}

	return $level;
}

if (!defined('\SQLITE3_OPEN_READWRITE')) {
	echo 'Le module de base de données SQLite3 n\'est pas disponible.' . PHP_EOL;
	exit(1);
}

/*
 * Configuration globale
 */

// Configuration externalisée
if (file_exists(__DIR__ . '/../' . CONFIG_FILE))
{
	require __DIR__ . '/../' . CONFIG_FILE;
}

// Configuration par défaut, si les constantes ne sont pas définies dans CONFIG_FILE
// (fallback)
if (!defined('Paheko\ROOT'))
{
	define('Paheko\ROOT', dirname(__DIR__));
}

\spl_autoload_register(function (string $classname): void {
	$classname = ltrim($classname, '\\');

	// Plugins
	if (substr($classname, 0, 14) == 'Paheko\\Plugin\\')
	{
		$classname = substr($classname, 14);
		$plugin_name = substr($classname, 0, strpos($classname, '\\'));
		$filename = str_replace('\\', '/', substr($classname, strpos($classname, '\\')+1));

		$path = Plugins::getPath(strtolower($plugin_name));

		// Plugin does not exist, just abort
		if (!$path) {
			return;
		}

		$path = $path . '/lib/' . $filename . '.php';
	}
	else
	{
		// PSR-0 autoload
		$filename = str_replace('\\', '/', $classname);
		$path = ROOT . '/include/lib/' . $filename . '.php';
	}

	if (file_exists($path)) {
		require_once $path;
	}
}, true);

if (!defined('Paheko\DATA_ROOT')) {
	define('Paheko\DATA_ROOT', ROOT . '/data');
}

if (!defined('Paheko\WWW_URI'))
{
	try {
		$uri = \KD2\HTTP::getRootURI(ROOT);
	}
	catch (\UnexpectedValueException $e) {
		$uri = null;
	}

	if ($uri == '/www/') {
		$uri = '/';
	}
	elseif ($uri !== null) {
		readfile(ROOT . '/sous-domaine.html');
		exit;
	}

	define('Paheko\WWW_URI', $uri);
	unset($uri);
}

$host = null;

if (!defined('Paheko\WWW_URL')) {
	$host = \KD2\HTTP::getHost();
}

if (WWW_URI === null || (!empty($host) && $host == 'host.unknown')) {
	$title = 'Impossible de détecter automatiquement l\'URL du site web.';
	$info = 'Consulter l\'aide pour configurer manuellement l\'URL avec la directive WWW_URL et WWW_URI.';
	$url ='https://fossil.kd2.org/paheko/wiki?name=Installation';

	if (PHP_SAPI == 'cli') {
		printf("\n/!\\ %s\n%s\n-> %s\n\n", $title, $info, $url);
	}
	else {
		printf('<h2 style="color: red">%s</h2><p><a href="%s">%s</a></p>', $title, $url, $info);
	}

	exit(1);
}

if (!defined('Paheko\WWW_URL') && $host !== null) {
	define('Paheko\WWW_URL', \KD2\HTTP::getScheme() . '://' . $host . WWW_URI);
}

static $default_config = [
	'CACHE_ROOT'            => DATA_ROOT . '/cache',
	'SHARED_CACHE_ROOT'     => DATA_ROOT . '/cache/shared',
	'WEB_CACHE_ROOT'        => DATA_ROOT . '/cache/web/%host%',
	'DB_FILE'               => DATA_ROOT . '/association.sqlite',
	'DB_SCHEMA'             => ROOT . '/include/data/schema.sql',
	'PLUGINS_ROOT'          => DATA_ROOT . '/plugins',
	'ALLOW_MODIFIED_IMPORT' => true,
	'SHOW_ERRORS'           => true,
	'MAIL_ERRORS'           => false,
	'ERRORS_REPORT_URL'     => null,
	'REPORT_USER_EXCEPTIONS' => 0,
	'ENABLE_TECH_DETAILS'   => true,
	'HTTP_LOG_FILE'         => null,
	'ENABLE_UPGRADES'       => true,
	'USE_CRON'              => false,
	'ENABLE_XSENDFILE'      => false,
	'DISABLE_EMAIL'         => false,
	'SMTP_HOST'             => false,
	'SMTP_USER'             => null,
	'SMTP_PASSWORD'         => null,
	'SMTP_PORT'             => 587,
	'SMTP_SECURITY'         => 'STARTTLS',
	'MAIL_RETURN_PATH'      => null,
	'MAIL_BOUNCE_PASSWORD'  => null,
	'MAIL_SENDER'           => null,
	'ADMIN_URL'             => WWW_URL . 'admin/',
	'NTP_SERVER'            => 'fr.pool.ntp.org',
	'ADMIN_COLOR1'          => '#20787a',
	'ADMIN_COLOR2'          => '#85b9ba',
	'ADMIN_BACKGROUND_IMAGE' => WWW_URL . 'admin/static/bg.png',
	'FORCE_CUSTOM_COLORS'   => false,
	'DISABLE_INSTALL_FORM'  => false,
	'FILE_STORAGE_BACKEND'  => 'SQLite',
	'FILE_STORAGE_CONFIG'   => null,
	'FILE_STORAGE_QUOTA'    => null,
	'API_USER'              => null,
	'API_PASSWORD'          => null,
	'PDF_COMMAND'           => 'auto',
	'PDF_USAGE_LOG'         => null,
	'PDFTOTEXT_COMMAND'     => null,
	'CALC_CONVERT_COMMAND'  => null,
	'CONTRIBUTOR_LICENSE'   => null,
	'SQL_DEBUG'             => null,
	'SYSTEM_SIGNALS'        => [],
	'LOCAL_LOGIN'           => null,
	'LEGAL_LINE'            => 'Hébergé par <strong>%1$s</strong>, %2$s',
	'ALERT_MESSAGE'         => null,
	'DISABLE_INSTALL_PING'  => false,
	'WOPI_DISCOVERY_URL'    => null,
	'SQLITE_JOURNAL_MODE'   => 'TRUNCATE',
];

foreach ($default_config as $const => $value)
{
	$const = sprintf('Paheko\\%s', $const);

	if (!defined($const))
	{
		define($const, $value);
	}
}

// Check SMTP_SECURITY value
if (SMTP_SECURITY) {
	$const = '\KD2\SMTP::' . strtoupper(SMTP_SECURITY);

	if (!defined($const)) {
		throw new \LogicException('Configuration: SMTP_SECURITY n\'a pas une valeur reconnue. Valeurs acceptées: STARTTLS, TLS, SSL, NONE.');
	}
}

// Used for private files, just in case WWW_URL is not the same domain as ADMIN_URL
define('Paheko\BASE_URL', str_replace('/admin/', '/', ADMIN_URL));

const HELP_URL = 'https://paheko.cloud/aide?from=%s';
const HELP_PATTERN_URL = 'https://paheko.cloud/%s';
const WEBSITE = 'https://fossil.kd2.org/paheko/';
const PING_URL = 'https://paheko.cloud/ping/';
const PLUGINS_URL = 'https://paheko.cloud/plugins/list.json';

const USER_TEMPLATES_CACHE_ROOT = CACHE_ROOT . '/utemplates';
const STATIC_CACHE_ROOT = CACHE_ROOT . '/static';
const SHARED_USER_TEMPLATES_CACHE_ROOT = SHARED_CACHE_ROOT . '/utemplates';
const SMARTYER_CACHE_ROOT = SHARED_CACHE_ROOT . '/compiled';

// PHP devrait être assez intelligent pour chopper la TZ système mais nan
// il sait pas faire (sauf sur Debian qui a le bon patch pour ça), donc pour
// éviter le message d'erreur à la con on définit une timezone par défaut
// Pour utiliser une autre timezone, il suffit de définir date.timezone dans
// un .htaccess ou dans CONFIG_FILE
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

class ValidationException extends UserException
{
}

class APIException extends \LogicException
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

ErrorManager::setContext([
	'root_directory'   => ROOT,
	'paheko_data_root' => DATA_ROOT,
	'paheko_version'   => paheko_version(),
]);

if (ERRORS_REPORT_URL)
{
	ErrorManager::setRemoteReporting(ERRORS_REPORT_URL, true);
}

ErrorManager::setProductionErrorTemplate(defined('Paheko\ERRORS_TEMPLATE') && ERRORS_TEMPLATE ? ERRORS_TEMPLATE : '<!DOCTYPE html><html><head><title>Erreur interne</title>
	<style type="text/css">
	body {font-family: sans-serif; background: #fff; }
	code, p, h1 { max-width: 400px; margin: 1em auto; display: block; }
	code { text-align: right; color: #666; }
	a { color: blue; }
	form { text-align: center; }
	</style></head><body><h1>Erreur interne</h1><p>Désolé mais le serveur a rencontré une erreur interne
	et ne peut répondre à votre requête. Merci de ré-essayer plus tard.</p>
	<p>Si vous suspectez un bug dans Paheko, vous pouvez suivre
	<a href="https://fossil.kd2.org/paheko/wiki?name=Rapporter+un+bug&p">ces instructions</a>
	pour le rapporter.</p>
	<if(sent)><p>Un-e responsable a été notifié-e et cette erreur sera corrigée dès que possible.</p></if>
	<if(logged)><code>L\'erreur a été enregistrée dans les journaux système (error.log) sous la référence : <b>{$ref}</b></code></if>
	<p><a href="' . WWW_URL . '">&larr; Retour à la page d\'accueil</a></p>
	</body></html>');

ErrorManager::setHtmlHeader('<!DOCTYPE html><html><head><meta charset="utf-8" /><style type="text/css">
	body { font-family: sans-serif; background: #fff; } * { margin: 0; padding: 0; }
	u, code b, i, h3 { font-style: normal; font-weight: normal; text-decoration: none; }
	#icn { color: #fff; font-size: 2em; float: right; margin: 1em; padding: 1em; background: #900; border-radius: 50%; }
	section header { background: #fdd; padding: 1em; }
	section article { margin: 1em; }
	section article h3, section article h4 { font-size: 1em; font-family: mono; }
	code { border: 1px dotted #ccc; display: block; }
	code b { margin-right: 1em; color: #999; }
	code u { background: #fcc; display: inline-block; width: 100%; }
	table { border-collapse: collapse; margin: 1em; } td, th { border: 1px solid #ccc; padding: .2em .5em; text-align: left; 
	vertical-align: top; }
	input { padding: .3em; margin: .5em; font-size: 1.2em; cursor: pointer; }
</style></head><body>
<pre id="icn"> \__/<br /> (xx)<br />//||\\\\</pre>
<section>
	<article>
	<h1>Une erreur s\'est produite</h1>
	<if(report)><form method="post" action="{$report_url}"><p><input type="hidden" name="report" value="{$report_json}" /><input type="submit" value="Rapporter l\'erreur aux développeur⋅euses de Paheko &rarr;" /></p></form></if>
	</article>
</section>
');

function user_error(UserException $e)
{
	if (REPORT_USER_EXCEPTIONS > 0) {
		\Paheko\Form::reportUserException($e);
	}

	if (PHP_SAPI == 'cli')
	{
		echo $e->getMessage();
	}
	else
	{
		// Flush any previous output, such as module HTML code etc.
		@ob_end_clean();

		// Don't use Template class as there might be an error there due do the context (eg. install/upgrade)
		$tpl = new \KD2\Smartyer(ROOT . '/templates/error.tpl');
		$tpl->setCompiledDir(SMARTYER_CACHE_ROOT);

		$tpl->assign('error', $e->getMessage());
		$tpl->assign('html_error', $e->getHTMLMessage());
		$tpl->assign('admin_url', ADMIN_URL);
		$tpl->display();
	}

	exit;
}

if (REPORT_USER_EXCEPTIONS < 2) {
	// Message d'erreur simple pour les erreurs de l'utilisateur
	ErrorManager::setCustomExceptionHandler('\Paheko\UserException', '\Paheko\user_error');
}

// Clé secrète utilisée pour chiffrer les tokens CSRF etc.
if (!defined('Paheko\SECRET_KEY'))
{
	if (!is_writable(ROOT)) {
		throw new \RuntimeException('Impossible de créer le fichier de configuration "'. CONFIG_FILE .'". Le répertoire "'. ROOT . '" n\'est pas accessible en écriture.');
	}
	$key = base64_encode(random_bytes(64));
	Install::setLocalConfig('SECRET_KEY', $key);
	define('Paheko\SECRET_KEY', $key);
}

// Intégration du secret pour les tokens CSRF
Form::tokenSetSecret(SECRET_KEY);

EntityManager::setGlobalDB(DB::getInstance());

Translate::setLocale('fr_FR');

/*
 * Vérifications pour enclencher le processus d'installation ou de mise à jour
 */

if (!defined('Paheko\INSTALL_PROCESS'))
{
	$exists = file_exists(DB_FILE);

	if (!$exists) {
		if (in_array('install.php', get_included_files())) {
			die('Erreur de redirection en boucle : problème de configuration ?');
		}

		Utils::redirect(ADMIN_URL . 'install.php');
	}

	$v = DB::getInstance()->version();

	if (version_compare($v, paheko_version(), '<'))
	{
		Utils::redirect(ADMIN_URL . 'upgrade.php');
	}
}
