<?php

namespace Paheko\UserTemplate;

use KD2\Brindille;
use KD2\Brindille_Exception;
use KD2\ErrorManager;
use KD2\Translate;

use Paheko\Config;
use Paheko\DB;
use Paheko\Plugins;
use Paheko\TemplateException;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

use Paheko\Entities\Files\File;
use Paheko\Files\Files;

use Paheko\Entities\Module;

use Paheko\UserTemplate\Modifiers;
use Paheko\UserTemplate\Functions;
use Paheko\UserTemplate\Sections;

use Paheko\Web\Cache as Web_Cache;

use const Paheko\{
	WWW_URL,
	WWW_URI,
	ADMIN_URL,
	BASE_URL,
	SHARED_USER_TEMPLATES_CACHE_ROOT,
	USER_TEMPLATES_CACHE_ROOT,
	DATA_ROOT,
	ROOT,
	PDF_COMMAND,
	SHOW_ERRORS
};

class UserTemplate extends \KD2\Brindille
{
	/**
	 * Path where local modules templates are stored
	 */
	const DIST_ROOT = ROOT . '/modules/';

	/**
	 * Relative template path, passed in __construct()
	 */
	public ?string $_tpl_path = null;

	/**
	 * Last modification timestamp of code
	 */
	protected int $modified;

	/**
	 * Local File object containing the source code
	 * (see __construct)
	 */
	protected ?File $file = null;

	/**
	 * Template source code, if template is created from a string
	 * (see __construct)
	 */
	protected ?string $code = null;

	/**
	 * Local path to template file
	 * (see __construct)
	 */
	protected ?string $path = null;

	/**
	 * Local filesystem path to cached compiled PHP code
	 * This can either be in a shared cache (cached code is shared between all organizations)
	 * if the source code comes from a DIST template, or in a cache root specific to this organization.
	 */
	protected string $cache_path;

	/**
	 * Parent template object, in case this template is included from another one.
	 */
	protected ?UserTemplate $parent = null;

	/**
	 * Related module object. This can be NULL if the template is not related to a module.
	 * (for example template from a string, used when sending newsletters)
	 */
	public ?Module $module = null;

	/**
	 * List of HTTP headers. The template can set some HTTP headers through the 'http'
	 * function. These headers are then returned to the HTTP client when the template
	 * is displayed.
	 */
	protected array $headers = [];

	/**
	 * Return TRUE if the filename is probably Brindille template code
	 * and should be treated as a UserTemplate
	 */
	static public function isTemplate(string $filename): bool
	{
		$dot = strrpos($filename, '.');

		// Templates with no extension are returned as HTML by default
		// unless {{:http type=...}} is used
		if ($dot === false) {
			return true;
		}

		$ext = substr($filename, $dot+1);

		switch ($ext) {
			case 'html':
			case 'htm':
			case 'tpl':
			case 'btpl':
			case 'b':
			case 'skel':
			case 'xml':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Create a UserTemplate object from a string.
	 *
	 * This is used for example when creating a newsletter, where
	 * the user can set tags and conditions to format the contents
	 * of the newsletter.
	 *
	 * UserTemplate objects created with this method always
	 * have auto-escaping turned off, and safe mode turned on,
	 * as it is mostly used for e-mailing.
	 */
	static public function createFromUserString(string $content): ?self
	{
		static $templates = [];

		// Don't create a UserTemplate instance if the string
		// doesn't contain any Brindille code
		if (false === strpos($content, '{{') || false === strpos($content, '}}')) {
			return null;
		}

		$hash = md5($content);

		// re-use a local cache of template instances
		if (isset($templates[$hash])) {
			return $templates[$hash];
		}

		$tpl = new UserTemplate(null);
		$tpl->setCode($content);
		$tpl->toggleSafeMode(true);

		// Disabling escape must be done after safe mode, or it will re-enable htmlspecialchars
		$tpl->setEscapeType(null);

		$templates[$hash] = $tpl;

		return $tpl;
	}

	/**
	 * Return an array of all root variables assigned to a template by default
	 */
	static public function getRootVariables()
	{
		static $root_variables = null;

		// Use local cache, don't recreate the array for every loaded template
		if (null !== $root_variables) {
			return $root_variables;
		}

		static $keys = ['color1', 'color2', 'site_disabled', 'org_name', 'org_address', 'org_address_public', 'org_email', 'org_phone', 'org_web', 'org_infos', 'currency', 'country', 'files', 'timezone'];

		$config = Config::getInstance();

		$files = $config::FILES;

		// Put URL in files array
		array_walk($files, function (&$v, $k) use ($config) {
			$v = $config->fileURL($k);
		});

		$cfg = array_intersect_key($config->asArray(), array_flip($keys));
		$cfg['files'] = $files;

		// @deprecated
		// FIXME: remove these legacy variables in a future version (1.4?)
		$cfg['nom_asso'] = $cfg['org_name'];
		$cfg['adresse_asso'] = $cfg['org_address'];
		$cfg['email_asso'] = $cfg['org_email'];
		$cfg['telephone_asso'] = $cfg['org_phone'];
		$cfg['site_asso'] = $cfg['org_web'];

		$cfg['user_fields'] = [
			'number'   => DynamicFields::getNumberField(),
			'login'    => DynamicFields::getLoginField(),
			'email'    => DynamicFields::getEmailFields(),
			'name'     => DynamicFields::getNameFields(),
			'name_sql' => DynamicFields::getNameFieldsSQL(),
		];

		$session = Session::getInstance();
		$is_logged = $session->isLogged();

		$root_variables = [
			'version_hash' => Utils::getVersionHash(),
			'root_url'     => WWW_URL,
			'root_uri'     => WWW_URI,
			'request_url'  => Utils::getRequestURI(),
			'admin_url'    => ADMIN_URL,
			'base_url'     => BASE_URL,
			'site_url'     => $config->getSiteURL(),
			'_GET'         => &$_GET,
			'_POST'        => &$_POST,
			'visitor_lang' => Translate::getHttpLang(),
			'config'       => $cfg,
			'now'          => time(),
			'is_logged'    => $is_logged,
			'logged_user'  => $is_logged ? $session->getUser()->asModuleArray() : null,
			'dialog'       => isset($_GET['_dialog']) ? ($_GET['_dialog'] ?: true) : false,
			'pdf_enabled'  => PDF_COMMAND !== null,
		];

		return $root_variables;
	}

	/**
	 * Constructs a new UserTemplate object for a module template path.
	 *
	 * The source code of the template will be taken either:
	 * 1. from the code imported into the organization, and stored inside
	 * the organization files (see File entity).
	 * 2. if a file of this path does not exist in the organization files,
	 * then we will try to use the default (DIST = distributed) modules
	 * files.
	 *
	 * Example: new UserTemplates('bookings/index.html')
	 * will try to load the org. file in "modules/bookings/index.html" if it
	 * exists. If not it will try to load ROOT . "/modules/bookings.html"
	 * from the local filesystem.
	 *
	 * If $path is left NULL, we assume we want to supply the source code
	 * using another method, eg. by using 'createFromUserString' method
	 */
	public function __construct(?string $path = null)
	{
		if ($path !== null) {
			$path = trim($path, '/');
			$this->_tpl_path = $path;
			$file = Files::get(File::CONTEXT_MODULES . '/' . $path);

			if ($file) {
				$this->setSourceFile($file);
			}
			else {
				$this->setSourcePath(self::DIST_ROOT . $path);
			}
		}

		Plugins::fire('usertemplate.init', false, ['template' => $this]);
	}

	/**
	 * Toggle safe mode
	 *
	 * If set to TRUE, then all functions and sections are removed, except foreach.
	 * Only modifiers can be used.
	 * Useful for templates where you don't want the user to be able to do SQL queries etc.
	 *
	 * @param  bool   $enable
	 * @return void
	 */
	public function toggleSafeMode(bool $safe_mode): void
	{
		if ($safe_mode) {
			// Make sure there are zero modifiers, functions, sections or compile blocks enabled
			$this->_functions = [];
			$this->_sections = [];
			$this->_blocks = [];
			$this->_modifiers = [];

			$this->registerDefaults();

			// Disable some advanced modifiers that could be used badly
			unset($this->_modifiers['sql_user_fields']);
			unset($this->_modifiers['markdown']);
			unset($this->_modifiers['sql_where']);
			unset($this->_modifiers['call']);
			unset($this->_modifiers['map']);
		}
		else {
			$this->registerAll();
		}
	}

	protected function registerModifiersArray(array $modifiers, string $class)
	{
		// Local modifiers
		foreach ($modifiers as $key => $value) {
			$modifier = [];

			if (is_string($value)) {
				$key = $value;
			}
			elseif (array_key_exists(0, $value)) {
				$modifier['types'] = $value;
			}
			else {
				$modifier = $value;
			}

			$modifier['callback'] ??= [$class, $key];
			$this->registerModifier($key, $modifier['callback'], $modifier['types'] ?? null, $modifier['pass_object'] ?? false);
		}
	}

	public function registerDefaults(): void
	{
		parent::registerDefaults();
		$this->assignArray(self::getRootVariables());

		$this->registerModifiersArray(CommonModifiers::MODIFIERS_LIST, CommonModifiers::class);
		$this->registerModifiersArray(Modifiers::MODIFIERS_LIST, Modifiers::class);

		// PHP modifiers
		foreach (CommonModifiers::PHP_MODIFIERS_LIST as $name => $types) {
			$this->registerModifier($name, $name, $types);
		}
	}

	public function registerAll()
	{
		$this->registerDefaults();

		// This must be in first place, as button function is override in Functions
		foreach (CommonFunctions::FUNCTIONS_LIST as $key => $name) {
			$this->registerFunction(is_int($key) ? $name : $key, is_int($key) ? [CommonFunctions::class, $name] : $name);
		}

		// Modules functions
		static $functions_classes = [
			Functions::class,
			Modules\TableFunctions::class,
		];

		foreach ($functions_classes as $class) {
			foreach ($class::FUNCTIONS_LIST as $name) {
				$this->registerFunction($name, [$class, $name]);
			}

			foreach ($class::COMPILE_FUNCTIONS_LIST as $name => $callback) {
				$this->registerCompileBlock($name, [$class, $callback]);
			}
		}

		// Local sections
		foreach (Sections::SECTIONS_LIST as $name) {
			$this->registerSection($name, [Sections::class, $name]);
		}

		foreach (Sections::COMPILE_SECTIONS_LIST as $name => $callback) {
			$this->registerCompileBlock($name, $callback);
		}
	}

	/**
	 * Load template code from a user-stored file
	 */
	public function setSourceFile(File $file)
	{
		if ($file->isDir()) {
			throw new UserException('Cannot construct a UserTemplate with a directory', 404);
		}

		$this->file = $file;
		$this->modified = $file->modified->getTimestamp();

		$this->cache_path = USER_TEMPLATES_CACHE_ROOT;

		$this->registerAll();
	}

	/**
	 * Load template code from a filesystem file
	 */
	public function setSourcePath(string $path)
	{
		if (!($this->modified = @filemtime($path))) {
			throw new \InvalidArgumentException('File not found: ' . $path);
		}

		$this->file = null;
		$this->path = $path;

		// Use shared cache for default (DIST) templates
		$this->cache_path = SHARED_USER_TEMPLATES_CACHE_ROOT;

		$this->registerAll();
	}

	/**
	 * Return TRUE if template code comes from a local DIST file and not from
	 * a user-supplied string (either a user-created template file or string).
	 *
	 * This allows us to remove restrictions on the number of recipients of
	 * emails sent using the {{:mail ...}} function.
	 */
	public function isTrusted(): bool
	{
		return isset($this->path) && !isset($this->code) && !isset($this->file);
	}

	/**
	 * Load template code from a string
	 */
	public function setCode(string $code): void
	{
		$this->code = $code;
		$this->file = null;
		$this->path = null;
		$this->modified = time();
		// Use custom cache for user templates
		$this->cache_path = USER_TEMPLATES_CACHE_ROOT;
	}

	/**
	 * Return compiled cache path
	 */
	protected function _getCachePath(): string
	{
		$hash = sha1($this->file ? $this->file->path : ($this->code ?: $this->path));
		return sprintf('%s/%s.php', $this->cache_path, $hash);
	}

	/**
	 * Return template code
	 */
	public function fetchCode(): string
	{
		if ($this->code) {
			return $this->code;
		}
		elseif ($this->file) {
			return $this->file->fetch();
		}
		else {
			return file_get_contents($this->path);
		}
	}

	public function fetchAndCatchErrors(): string
	{
		try {
			return $this->fetch();
		}
		catch (TemplateException $e) {
			// Always throw error for code outside of templates (eg. mailing body)
			if ($this->code) {
				throw $e;
			}
			// If we are in debug mode (SHOW_ERRORS)
			// or if this is an error from a user-created module and the logged user is an admin,
			// we want to see the actual template code for the error
			elseif (($this->path && SHOW_ERRORS)
				|| ($this->file && Session::getInstance()->isAdmin())) {
				$this->displayError($e);
				exit;
			}
			// If this is an error from a module created by the user, just display some basic message
			elseif ($this->module && $this->file) {
				$message = sprintf('Erreur dans "%s" :' . "\n%s\n" . '(Contactez votre administrateur⋅trice)', $this->file->path, $e->getMessage());
				$e = new UserException($message, 500, $e);
				\Paheko\user_error($e);
				exit;
			}
			// This shouldn't happen?
			// Some possible case is an UserTemplate outside of a module
			else {
				throw new \LogicException('Template error outside of a module: ' . $e->getMessage(), 0, $e);
			}
		}
	}

	public function display(): void
	{
		$compiled_path = $this->_getCachePath();

		$return = $this->displayUsingCache([$this, 'fetchCode'], $compiled_path, $this->modified);

		// If template returned 'STOP' string (eg. from the redirect function),
		// call exit now. Don't call exit in Brindille functions, or it might mess
		// with execution of other stuff, for example committing a DB transaction
		if ($return === 'STOP') {
			exit;
		}
	}

	public function fetch(): string
	{
		ob_start();

		try {
			$this->display();
		}
		catch (\Throwable $e) {
			ob_end_clean();

			// Make sure we always throw a TemplateException
			if ($e instanceof Brindille_Exception) {
				$e = new TemplateException($e->getMessage(), $e->getCode(), $e);
			}

			throw $e;
		}

		return ob_get_clean();
	}

	/**
	 * Export template to PDF or HTML, and store it in files
	 */
	public function fetchAsAttachment(?string $type = null): File
	{
		$content = $this->fetch();
		$type ??= $this->getContentType();
		$name = $this->getHeader('filename') ?? 'document';

		// Sanitize file name
		$name = Utils::transliterateToAscii($name);
		$name = preg_replace('/[^\w\d\.]+/U', ' ', $name);
		$name = substr($name, -128);

		if ($type == 'application/pdf' && substr($name, -4) !== '.pdf') {
			$name .= '.pdf';
		}

		File::validateFileName($name);

		$target = File::CONTEXT_ATTACHMENTS . '/' . md5($content) . '/' . $name;

		if ($type == 'application/pdf') {
			$tmp = Utils::filePDF($content);
			$file = Files::createFromPath($target, $tmp);
			Utils::safe_unlink($tmp);
		}
		else {
			$file = Files::createFromString($target, $content);
		}

		return $file;
	}

	/**
	 * Simulate fetching a template as if it was requested by a HTTP GET request,
	 * just by supplying its URI address.
	 *
	 * This is used to attach a template to an email for example:
	 * {{:mail to="me@example.org" attach_from="/m/welcome/page.html?id=45"}}
	 */
	public function fetchToAttachment(string $uri): File
	{
		$parts = explode('?', $uri, 2);
		$path = $parts[0] ?? '';
		$query = $parts[1] ?? '';
		parse_str($query, $qs);

		$ut = new UserTemplate($path);
		$ut->setModule($this->module);
		$ut->assignArray(['_POST' => [], '_GET' => $qs]);
		return $ut->fetchAsAttachment();
	}

	/**
	 * Set template HTTP header
	 */
	public function setHeader(string $name, string $value): void
	{
		if ($this->parent) {
			// Setting headers on included template does not make sense,
			// instead pass this to parent template
			$this->parent->setHeader($name, $value);
		}
		else {
			$this->headers[$name] = $value;
		}
	}

	/**
	 * Return template HTTP header
	 */
	public function getHeader(string $name): ?string
	{
		return $this->headers[$name] ?? null;
	}

	/**
	 * Dump saved HTTP headers to current HTTP client
	 */
	public function dumpHeaders(): void
	{
		if (isset($this->headers['code'])) {
			$code = $this->headers['code'];

			static $codes = [
				100 => 'Continue',
				101 => 'Switching Protocols',
				102 => 'Processing',
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				207 => 'Multi-Status',
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				306 => 'Switch Proxy',
				307 => 'Temporary Redirect',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				418 => 'I\'m a teapot',
				422 => 'Unprocessable Entity',
				423 => 'Locked',
				424 => 'Failed Dependency',
				425 => 'Unordered Collection',
				426 => 'Upgrade Required',
				449 => 'Retry With',
				450 => 'Blocked by Windows Parental Controls',
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported',
				506 => 'Variant Also Negotiates',
				507 => 'Insufficient Storage',
				509 => 'Bandwidth Limit Exceeded',
				510 => 'Not Extended',
			];

			if (!isset($codes[$code])) {
				throw new TemplateException('Code HTTP inconnu: ' . $code);
			}

			header(sprintf('HTTP/1.1 %d %s', $code, $codes[$code]), true);
		}

		if (isset($this->headers['type'])) {
			header(sprintf('Content-Type: %s; charset=utf-8', $this->headers['type']), true);
		}

		if (isset($this->headers['disposition'])) {
			header(sprintf('Content-Disposition: %s; filename="%s"',
				$this->headers['disposition'],
				Utils::safeFileName($this->headers['filename'])
			), true);

			if ($this->headers['disposition'] === 'inline') {
				// Seems that this is required for Chrome
				// @see https://stackoverflow.com/questions/71679544/content-disposition-inline-filename-not-working
				header('Cache-Control: no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0', true);
			}
		}
	}

	/**
	 * Return status code of the current template,
	 * set by {{:http code=XXX}}
	 */
	public function getStatusCode(): int
	{
		return (int) ($this->headers['code'] ?? 200);
	}

	/**
	 * Return HTTP Content-Type header, as set by
	 * {{:http type=XXX}}
	 */
	public function getContentType(): string
	{
		return $this->headers['type'] ?? 'text/html';
	}

	/**
	 * Will serve the current template file, as if requested
	 * from a HTTP request.
	 */
	public function serve(): void
	{
		$path = $this->path ?? $this->file->path;

		if (!self::isTemplate($path)) {
			throw new \InvalidArgumentException('Not a valid template file extension: ' . $this->path);
		}

		$content = $this->fetchAndCatchErrors();

		$this->dumpHeaders();

		if ($this->getContentType() == 'application/pdf') {
			Utils::streamPDF($content);
		}
		else {
			echo $content;
		}
	}

	/**
	 * Display custom error page for Brindille/Template errors.
	 * Because we want to give the admin enough information on the issue
	 * so they can fix the issue in the code.
	 */
	public function displayError(\Exception $e)
	{
		http_response_code(500);
		echo '<!DOCTYPE html><html><head><meta charse="utf-8" /><title>Erreur Brindille</title><style type="text/css">';
		echo '* { margin: 0; padding: 0; font-size: unset; } body { background: #fee; font-family: sans-serif; } ';
		echo 'main { background: #fff; padding: 1em; border-radius: 1em; max-width: 50em; margin: 1em auto; }';
		echo 'h1 { font-size: 1.5em; }';
		echo 'header { border-bottom: 5px darkred solid; margin-bottom: 1em; padding-bottom: 1em; }';
		echo 'header h2, header h3 { font-family: monospace; font-size: 1.5em; margin: .4rem 0; }';
		echo '#icn { color: #fff; font-size: 2em; float: right; margin: 0 1em; padding: 1em; background: #900; border-radius: 50%; }';
		echo 'section { font-family: monospace; }';
		echo 'section h1 { white-space: pre-wrap; }';
		echo 'section table { margin: 1em 0; border: 1px solid #ccc; border-collapse: collapse; }';
		echo 'section th { vertical-align: top; text-align: right; padding: .3em }';
		echo 'section table td { white-space: pre-wrap; padding: .3em }';
		echo 'section .current { background: #fcc; }';
		echo 'footer { margin-top: 1em; border-top: 5px solid #ccc; padding: 1em; }';
		echo 'footer p { margin: .5em 0; } footer a { display: inline-block; padding: .3em; border: 2px solid #ddd; color: #000; border-radius: .3em; } ';
		echo '</style></head><body><main><header><pre id="icn"> \__/<br /> (xx)<br />//||\\\\</pre><h1>';

		echo 'Erreur Brindille';

		echo '</h1><h2>';

		printf('Module : %s', $this->module->name);

		echo '</h2><h3>Fichier : ';

		echo $this->file->name ?? str_replace(ROOT, '…', $this->path);

		echo '</h3></header><section><h1>';

		echo preg_replace('/\r\n|\n|\r/', '', nl2br(htmlspecialchars($e->getMessage())));

		echo '</h1><table>';

		if (preg_match('/Line (\d+)\s*:/i', $e->getMessage(), $match)) {
			$line = $match[1] - 1;

			if ($this->file) {
				$file = explode("\n", $this->file->fetch());
			}
			else {
				$file = file($this->path);
			}

			$start = max(0, $line - 5);
			$max = min(count($file), $line + 6);

			for ($i = $start; $i < $max; $i++) {
				printf('<tr class="%s"><th>%d</th><td>%s</td></tr>', $i == $line ? 'current' : '', $i + 1, htmlspecialchars($file[$i]));
			}

			echo '</table>';
		}

		echo '</section><footer>';

		if ($this->file) {
			printf('<p>Vérifiez que vous avez bien la dernière version du module.</p>
				<p>Si c\'est le cas, contactez l\'auteur⋅e du module : <a href="%s">%s</a></p>
				<p><strong>Ceci n\'est pas une erreur dans Paheko, merci de ne pas contacter le support Paheko :-)</strong></p>',
				htmlspecialchars($this->module->author_url ?? ''),
				htmlspecialchars($this->module->author ?? 'inconnu')
			);
		}

		echo '</footer></body></html>';
	}

	/**
	 * Override parent Brindille class _callFunction, to catch and throw TemplateException and get line number
	 */
	public function _callFunction(string $name, array $params, int $line) {
		try {
			return call_user_func($this->_functions[$name], $params, $this, $line);
		}
		catch (Brindille_Exception | TemplateException $e) {
			$message = sprintf("line %d: function '%s' has returned an error: %s\nParameters: %s", $line, $name, $e->getMessage(), self::printVariable($params));
			throw new TemplateException($message, $e->getCode(), $e);
		}
	}

	/**
	 * This is used by the include template function, to establish
	 * where an included template is included from
	 */
	public function setParent(UserTemplate $tpl): void
	{
		$this->parent = $tpl;
		$this->setModule($tpl->module);
	}

	/**
	 * Set additional variables specific to modules
	 */
	public function setModule(?Module $module): void
	{
		if (!$module) {
			return;
		}

		$tables = $module->listTables();

		$this->module = $module;
		$this->assign('module', array_merge($module->asArray(false), [
			'config'       => json_decode(json_encode($module->config), true),
			'url'          => $module->url(),
			'public_url'   => $module->public_url(),
			'storage_root' => $module->storage_root(),
			'table'        => in_array($module::DOCUMENTS_TABLE_NAME, $tables) ? $module::DOCUMENTS_TABLE_NAME : null,
			'tables'       => array_values($tables),
		]));
	}
}
