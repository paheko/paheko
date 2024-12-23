<?php

namespace Paheko;

use KD2\ErrorManager;
use KD2\Security;
use KD2\Form;
use KD2\Translate;
use KD2\DB\EntityManager;

$start_timer = microtime(true);

foreach ($_ENV as $key => $value) {
	if (strpos($key, 'PAHEKO_') === 0) {
		$key = substr($key, strlen('PAHEKO_'));
		define('Paheko\\' . $key, $value);
	}
}

if (!defined('Paheko\CONFIG_FILE')) {
	define('Paheko\CONFIG_FILE', __DIR__ . '/../config.local.php');
}

require_once __DIR__ . '/lib/KD2/ErrorManager.php';

ErrorManager::enable(ErrorManager::DEVELOPMENT);
ErrorManager::setLogFile(__DIR__ . '/data/error.log');

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


if (!defined('\SQLITE3_OPEN_READWRITE')) {
	echo 'Le module de base de données SQLite3 n\'est pas disponible.' . PHP_EOL;
	exit(1);
}

/*
 * Configuration globale
 */

// Configuration externalisée
if (null !== CONFIG_FILE && file_exists(CONFIG_FILE)) {
	require CONFIG_FILE;
}

// Configuration par défaut, si les constantes ne sont pas définies dans CONFIG_FILE
// (fallback)
if (!defined('Paheko\ROOT')) {
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

if (!defined('Paheko\WWW_URI')) {
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
	// USER_CONFIG_FILE is used in single-user setup (Debian/Windows)
	// to be able to add user-specific config constants, even though we already
	// have a config.local.php for OS-specific stuff, this also allows
	// to remove LOCAL_USER and have a multi-user setup on a single computer
	'USER_CONFIG_FILE'      => null,
	'CACHE_ROOT'            => DATA_ROOT . '/cache',
	'SHARED_CACHE_ROOT'     => DATA_ROOT . '/cache/shared',
	'WEB_CACHE_ROOT'        => DATA_ROOT . '/cache/web/%host%',
	'DB_FILE'               => DATA_ROOT . '/association.sqlite',
	'DB_SCHEMA'             => ROOT . '/include/data/schema.sql',
	'PLUGINS_ROOT'          => DATA_ROOT . '/plugins',
	'PLUGINS_ALLOWLIST'     => null,
	'PLUGINS_BLOCKLIST'     => null,
	'ALLOW_MODIFIED_IMPORT' => false,
	'SHOW_ERRORS'           => true,
	'MAIL_ERRORS'           => false,
	'ERRORS_REPORT_URL'     => null,
	'REPORT_USER_EXCEPTIONS' => 0,
	'ENABLE_TECH_DETAILS'   => true,
	'HTTP_LOG_FILE'         => null,
	'WEBDAV_LOG_FILE'       => null,
	'WOPI_LOG_FILE'         => null,
	'ENABLE_UPGRADES'       => true,
	'USE_CRON'              => false,
	'ENABLE_XSENDFILE'      => false,
	'DISABLE_EMAIL'         => false,
	'SMTP_HOST'             => null,
	'SMTP_USER'             => null,
	'SMTP_PASSWORD'         => null,
	'SMTP_PORT'             => 587,
	'SMTP_SECURITY'         => 'STARTTLS',
	'SMTP_HELO_HOSTNAME'    => null,
	'MAIL_RETURN_PATH'      => null,
	'MAIL_BOUNCE_PASSWORD'  => null,
	'MAIL_SENDER'           => null,
	'ADMIN_URL'             => WWW_URL . 'admin/',
	'NTP_SERVER'            => 'fr.pool.ntp.org',
	'ADMIN_COLOR1'          => '#20787a',
	'ADMIN_COLOR2'          => '#85b9ba',
	'ADMIN_BACKGROUND_IMAGE' => WWW_URL . 'admin/static/bg.png',
	'ADMIN_CUSTOM_CSS'      => null,
	'FORCE_CUSTOM_COLORS'   => false,
	'DISABLE_INSTALL_FORM'  => false,
	'FILE_STORAGE_BACKEND'  => 'SQLite',
	'FILE_STORAGE_CONFIG'   => null,
	'FILE_STORAGE_QUOTA'    => null,
	'FILE_VERSIONING_POLICY'   => null,
	'FILE_VERSIONING_MAX_SIZE' => null,
	'API_USER'              => null,
	'API_PASSWORD'          => null,
	'PDF_COMMAND'           => 'auto',
	'PDF_USAGE_LOG'         => null,
	'SQL_DEBUG'             => null,
	'ENABLE_PROFILER'       => false,
	'SYSTEM_SIGNALS'        => [],
	'LOCAL_LOGIN'           => null,
	'LEGAL_HOSTING_DETAILS' => null,
	'ALERT_MESSAGE'         => null,
	'DISABLE_INSTALL_PING'  => false,
	'WOPI_DISCOVERY_URL'    => null,
	'SQLITE_JOURNAL_MODE'   => 'TRUNCATE',
	'LOCAL_ADDRESSES_ROOT'  => null,
];

foreach ($default_config as $const => $value)
{
	$const = sprintf('Paheko\\%s', $const);

	if (!defined($const))
	{
		define($const, $value);
	}
}

/**
 * @deprecated Remove DOCUMENT_THUMBNAIL_COMMANDS constant in 1.4.0
 */
if (!defined('Paheko\ENABLE_FILE_THUMBNAILS')) {
	define('Paheko\ENABLE_FILE_THUMBNAILS', defined('Paheko\DOCUMENT_THUMBNAIL_COMMANDS') ? constant('Paheko\DOCUMENT_THUMBNAIL_COMMANDS') !== null : true);
}

/**
 * @deprecated Remove CALC_CONVERT_COMMAND/PDFTOTEXT_COMMAND/DOCUMENT_THUMBNAIL_COMMANDS constants in 1.4.0
 */
if (!defined('Paheko\CONVERSION_TOOLS')) {
	$tools = [];

	if (defined('Paheko\CALC_CONVERT_COMMAND') && constant('Paheko\CALC_CONVERT_COMMAND') !== null) {
		$tools[] = constant('Paheko\CALC_CONVERT_COMMAND');
	}

	if (defined('Paheko\PDFTOTEXT_COMMAND') && constant('Paheko\PDFTOTEXT_COMMAND') !== null) {
		$tools[] = constant('Paheko\PDFTOTEXT_COMMAND');
	}

	if (defined('Paheko\DOCUMENT_THUMBNAIL_COMMANDS') && constant('Paheko\DOCUMENT_THUMBNAIL_COMMANDS') !== null) {
		$tools[] = array_merge($tools, constant('Paheko\DOCUMENT_THUMBNAIL_COMMANDS'));
	}

	define('Paheko\CONVERSION_TOOLS', count($tools) ? $tools : null);
	unset($tools);
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
define('Paheko\ADMIN_URI', preg_replace('!(^https?://[^/]+)!', '', ADMIN_URL));

const HELP_URL = 'https://paheko.cloud/aide?from=%s';
const HELP_PATTERN_URL = 'https://paheko.cloud/%s';
const WEBSITE = 'https://fossil.kd2.org/paheko/';
const PING_URL = 'https://paheko.cloud/ping/';

const USER_TEMPLATES_CACHE_ROOT = CACHE_ROOT . '/utemplates';
const STATIC_CACHE_ROOT = CACHE_ROOT . '/static';
const SHARED_USER_TEMPLATES_CACHE_ROOT = SHARED_CACHE_ROOT . '/utemplates';
const SMARTYER_CACHE_ROOT = SHARED_CACHE_ROOT . '/compiled';

// Used to get around some providers misconfiguration issues
if (isset($_SERVER['HTTP_X_OVHREQUEST_ID'])) {
	define('Paheko\HOSTING_PROVIDER', 'OVH');
}
else {
	define('Paheko\HOSTING_PROVIDER', null);
}

if (ENABLE_PROFILER) {
	define('Paheko\PROFILER_START_TIME', $start_timer);

	register_shutdown_function([Utils::class, 'showProfiler']);
}

// PHP devrait être assez intelligent pour chopper la TZ système mais nan
// il sait pas faire (sauf sur Debian qui a le bon patch pour ça), donc pour
// éviter le message d'erreur à la con on définit une timezone par défaut
if (!ini_get('date.timezone') || ini_get('date.timezone') === 'UTC') {
	if (($tz = @date_default_timezone_get()) && $tz !== 'UTC') {
		ini_set('date.timezone', $tz);
	}
	else {
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
ErrorManager::setEnvironment(SHOW_ERRORS ? ErrorManager::DEVELOPMENT : ErrorManager::PRODUCTION | ErrorManager::CLI_DEVELOPMENT);
ErrorManager::setLogFile(DATA_ROOT . '/error.log');

// activer l'envoi de mails si besoin est
if (MAIL_ERRORS) {
	ErrorManager::setEmail(MAIL_ERRORS);
}

if (ERRORS_REPORT_URL) {
	ErrorManager::setRemoteReporting(ERRORS_REPORT_URL, true);
}

ErrorManager::setContext([
	'root_directory'   => ROOT,
	'paheko_data_root' => DATA_ROOT,
	'paheko_version'   => paheko_version(),
]);


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

	if (PHP_SAPI == 'cli') {
		echo $e->getMessage();
		exit;
	}

	try {
		// Flush any previous output, such as module HTML code etc.
		@ob_end_clean();

		if ($e->getCode() >= 400) {
			http_response_code($e->getCode());
		}

		// Don't use Template class as there might be an error there due do the context (eg. install/upgrade)
		$tpl = new \KD2\Smartyer(ROOT . '/templates/error.tpl');
		$tpl->setCompiledDir(SMARTYER_CACHE_ROOT);

		$tpl->assign('error', $e->getMessage());
		$tpl->assign('html_error', $e->getHTMLMessage());
		$tpl->assign('admin_url', ADMIN_URL);
		$tpl->display();
	}
	catch (\Throwable $e) {
		ErrorManager::reportException($e, true);
	}

	exit;
}

if (REPORT_USER_EXCEPTIONS < 2) {
	// Message d'erreur simple pour les erreurs de l'utilisateur
	ErrorManager::setCustomExceptionHandler('\Paheko\UserException', '\Paheko\user_error');
}

// Clé secrète utilisée pour chiffrer les tokens CSRF etc.
if (!defined('Paheko\SECRET_KEY')) {
	$key = base64_encode(random_bytes(64));
	Install::setConfig(CONFIG_FILE, 'SECRET_KEY', $key);
	define('Paheko\SECRET_KEY', $key);
}

// Define a local secret key derived of the main secret key and the data root
// This is to make sure that in a multi-instance setup you don't reuse the same secret
// between instances.
define('Paheko\LOCAL_SECRET_KEY', sha1(SECRET_KEY . DATA_ROOT));

// Intégration du secret pour les tokens CSRF
Form::tokenSetSecret(LOCAL_SECRET_KEY);

EntityManager::setGlobalDB(DB::getInstance());

Translate::setLocale('fr_FR');

// This is specific to OVH and other hosting providers who don't set up their servers properly
// see https://www.prestashop.com/forums/topic/393496-prestashop-16-webservice-authentification-on-ovh/
if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
	@list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
}

// Check if we need to redirect to install or upgrade pages
if (!defined('Paheko\SKIP_STARTUP_CHECK')) {
	if (!DB::isInstalled()) {
		if (in_array('install.php', get_included_files())) {
			die('Erreur de redirection en boucle : problème de configuration ?');
		}

		Utils::redirect(ADMIN_URL . 'install.php');
	}

	if (DB::isUpgradeRequired()) {
		if (!empty($_POST)) {
			http_response_code(500);
			readfile(ROOT . '/templates/static/upgrade_post.html');
			exit;
		}

		Utils::redirect(ADMIN_URL . 'upgrade.php');
	}

	if (DB::isVersionTooNew()) {
		throw new \LogicException('Database version is higher than installed version of code.');
	}

	if (Config::getInstance()->timezone) {
		@date_default_timezone_set(Config::getInstance()->timezone);
	}
}
