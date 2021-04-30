<?php

namespace Garradin;

use KD2\ErrorManager;
use KD2\Security;
use KD2\Form;
use KD2\DB\EntityManager;

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

if (!defined('\SQLITE3_OPEN_READWRITE')) {
	echo 'Le module de base de données SQLite3 n\'est pas disponible.' . PHP_EOL;
	exit(1);
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

\spl_autoload_register(function (string $classname) {
	$classname = ltrim($classname, '\\');

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

	if (file_exists($path)) {
		require_once $path;
	}
}, true);

if (!defined('Garradin\DATA_ROOT')) {
	// Migrate plugins, cache and SQLite to data/ subdirectory (version 1.1)
	if (!file_exists(ROOT . '/data/association.sqlite') && file_exists(ROOT . '/association.sqlite')) {
		Upgrade::moveDataRoot();
	}

	define('Garradin\DATA_ROOT', ROOT . '/data');
}

if (!defined('Garradin\WWW_URI'))
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

	define('Garradin\WWW_URI', $uri);
	unset($uri);
}

if (!defined('Garradin\WWW_URL')) {
	$host = \KD2\HTTP::getHost();
}

if (WWW_URI === null || (!empty($host) && $host == 'host.unknown')) {
	$title = 'Impossible de détecter automatiquement l\'URL du site web.';
	$info = 'Consulter l\'aide pour configurer manuellement l\'URL avec la directive WWW_URL et WWW_URI.';
	$url ='https://fossil.kd2.org/garradin/wiki?name=Installation';

	if (PHP_SAPI == 'cli') {
		printf("\n/!\\ %s\n%s\n-> %s\n\n", $title, $info, $url);
	}
	else {
		printf('<h2 style="color: red">%s</h2><p><a href="%s">%s</a></p>', $title, $url, $info);
	}

	exit(1);
}

if (!defined('Garradin\WWW_URL')) {
	define('Garradin\WWW_URL', \KD2\HTTP::getScheme() . '://' . $host . WWW_URI);
}

static $default_config = [
	'CACHE_ROOT'            => DATA_ROOT . '/cache',
	'SHARED_CACHE_ROOT'     => DATA_ROOT . '/cache/shared',
	'DB_FILE'               => DATA_ROOT . '/association.sqlite',
	'DB_SCHEMA'             => ROOT . '/include/data/schema.sql',
	'PLUGINS_ROOT'          => DATA_ROOT . '/plugins',
	'PREFER_HTTPS'          => false,
	'ALLOW_MODIFIED_IMPORT' => true,
	'PLUGINS_SYSTEM'        => '',
	'SHOW_ERRORS'           => true,
	'MAIL_ERRORS'           => false,
	'ERRORS_REPORT_URL'     => null,
	'ENABLE_TECH_DETAILS'   => true,
	'USE_CRON'              => false,
	'ENABLE_XSENDFILE'      => false,
	'SMTP_HOST'             => false,
	'SMTP_USER'             => null,
	'SMTP_PASSWORD'         => null,
	'SMTP_PORT'             => 587,
	'SMTP_SECURITY'         => 'STARTTLS',
	'ADMIN_URL'             => WWW_URL . 'admin/',
	'NTP_SERVER'            => 'fr.pool.ntp.org',
	'ENABLE_AUTOMATIC_BACKUPS' => true,
	'ADMIN_COLOR1'          => '#9c4f15',
	'ADMIN_COLOR2'          => '#d98628',
	'FILE_STORAGE_BACKEND'  => 'SQLite',
	'FILE_STORAGE_CONFIG'   => null,
	'FILE_STORAGE_QUOTA'    => null,
	'API_USER'              => null,
	'API_PASSWORD'          => null,
];

foreach ($default_config as $const => $value)
{
	$const = sprintf('Garradin\\%s', $const);

	if (!defined($const))
	{
		define($const, $value);
	}
}

if (!defined('Garradin\ADMIN_BACKGROUND_IMAGE')) {
	define('Garradin\ADMIN_BACKGROUND_IMAGE', ADMIN_URL . 'static/gdin_bg.png');
}

const WEBSITE = 'https://fossil.kd2.org/garradin/';
const PLUGINS_URL = 'https://garradin.eu/plugins/list.json';

const USER_TEMPLATES_CACHE_ROOT = CACHE_ROOT . '/utemplates';
const STATIC_CACHE_ROOT = CACHE_ROOT . '/static';
const SHARED_USER_TEMPLATES_CACHE_ROOT = SHARED_CACHE_ROOT . '/utemplates';
const SMARTYER_CACHE_ROOT = SHARED_CACHE_ROOT . '/compiled';

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

/*
 * Gestion des erreurs et exceptions
 */

class UserException extends \LogicException
{
	protected $details;

	public function setMessage(string $message) {
		$this->message = $message;
	}

	public function setDetails($details) {
		$this->details = $details;
	}

	public function getDetails() {
		return $this->details;
	}

	public function hasDetails(): bool {
		return $this->details !== null;
	}

	public function getDetailsHTML() {
		if (func_num_args() == 1) {
			$details = func_get_arg(0);
		}
		else {
			$details = $this->details;
		}

		if (null === $details) {
			return '<em>(nul)</em>';
		}

		if ($details instanceof \DateTimeInterface) {
			return $details->format('d/m/Y');
		}

		if (!is_array($details)) {
			return nl2br(htmlspecialchars($details));
		}

		$out = '<table>';

		foreach ($details as $key => $value) {
			$out .= sprintf('<tr><th>%s</th><td>%s</td></tr>', htmlspecialchars($key), $this->getDetailsHTML($value));
		}

		$out .= '</table>';

		return $out;
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
	'rootDirectory'      => ROOT,
	'garradin_data_root' => DATA_ROOT,
	'garradin_version'   => garradin_version(),
]);

if (ERRORS_REPORT_URL)
{
	ErrorManager::setRemoteReporting(ERRORS_REPORT_URL, true);
}

ErrorManager::setProductionErrorTemplate(defined('Garradin\ERRORS_TEMPLATE') && ERRORS_TEMPLATE ? ERRORS_TEMPLATE : '<!DOCTYPE html><html><head><title>Erreur interne</title>
	<style type="text/css">
	body {font-family: sans-serif; }
	code, p, h1 { max-width: 400px; margin: 1em auto; display: block; }
	code { text-align: right; color: #666; }
	a { color: blue; }
	form { text-align: center; }
	</style></head><body><h1>Erreur interne</h1><p>Désolé mais le serveur a rencontré une erreur interne
	et ne peut répondre à votre requête. Merci de ré-essayer plus tard.</p>
	<p>Si vous suspectez un bug dans Garradin, vous pouvez suivre 
	<a href="https://fossil.kd2.org/garradin/wiki?name=Rapporter+un+bug&p">ces instructions</a>
	pour le rapporter.</p>
	<if(sent)><p>Un-e responsable a été notifié-e et cette erreur sera corrigée dès que possible.</p></if>
	<if(logged)><code>L\'erreur a été enregistrée dans les journaux système (error.log) sous la référence : <b>{$ref}</b></code></if>
	<p><a href="' . WWW_URL . '">&larr; Retour à la page d\'accueil</a></p>
	</body></html>');

ErrorManager::setHtmlHeader('<!DOCTYPE html><meta charset="utf-8" /><style type="text/css">
	body { font-family: sans-serif; } * { margin: 0; padding: 0; }
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
</style>
<pre id="icn"> \__/<br /> (xx)<br />//||\\\\</pre>
<section>
	<article>
	<h1>Une erreur s\'est produite</h1>
	<if(report)><form method="post" action="{$report_url}"><p><input type="hidden" name="report" value="{$report_json}" /><input type="submit" value="Rapporter l\'erreur aux développeur⋅euses de Garradin &rarr;" /></p></form></if>
	</article>
</section>
');

function user_error(\Exception $e)
{
	if (PHP_SAPI == 'cli')
	{
		echo $e->getMessage();
	}
	else
	{
		$tpl = Template::getInstance();

		$tpl->assign('error', $e->getMessage());
		$tpl->assign('admin_url', ADMIN_URL);
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
	$key = base64_encode(random_bytes(64));
	Install::setLocalConfig('SECRET_KEY', $key);
	define('Garradin\SECRET_KEY', $key);
}

// Intégration du secret pour les tokens
Form::tokenSetSecret(SECRET_KEY);

EntityManager::setGlobalDB(DB::getInstance());

/*
 * Vérifications pour enclencher le processus d'installation ou de mise à jour
 */

if (!defined('Garradin\INSTALL_PROCESS') && !defined('Garradin\UPGRADE_PROCESS'))
{
	if (!file_exists(DB_FILE)) {
		if (in_array('install.php', get_included_files())) {
			die('Erreur de redirection en boucle : problème de configuration ?');
		}

		Utils::redirect(ADMIN_URL . 'install.php');
	}

	$v = DB::getInstance()->version();

	if (version_compare($v, garradin_version(), '<'))
	{
		Utils::redirect(ADMIN_URL . 'upgrade.php');
	}
}
