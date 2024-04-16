<?php

namespace Paheko\UserTemplate;

use KD2\Brindille;
use KD2\Brindille_Exception;
use KD2\Translate;

use Paheko\Config;
use Paheko\DB;
use Paheko\Plugins;
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

use const Paheko\{WWW_URL, WWW_URI, ADMIN_URL, BASE_URL, SHARED_USER_TEMPLATES_CACHE_ROOT, USER_TEMPLATES_CACHE_ROOT, DATA_ROOT, ROOT, PDF_COMMAND};

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
	 * Default escaping used for displayed variables.
	 * ("auto-escaping")
	 */
	protected $escape_default = 'html';

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
		$tpl->setEscapeDefault(null);

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

		static $keys = ['color1', 'color2', 'site_disabled', 'org_name', 'org_address', 'org_email', 'org_phone', 'org_web', 'org_infos', 'currency', 'country', 'files'];

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

			if ($file = Files::get(File::CONTEXT_MODULES . '/' . $path)) {
				$this->setLocalSource($file);
			}
			else {
				$this->setSource(self::DIST_ROOT . $path);
			}

			$this->assignArray(self::getRootVariables());

			$this->registerAll();
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
			$this->_modifiers_with_instance = [];

			// Register default Brindille modifiers instead
			$this->registerDefaults();
		}
		else {
			$this->registerAll();
		}
	}

	/**
	 * Set default escaping modifier
	 */
	public function setEscapeDefault(?string $default): void
	{
		$this->escape_default = $default;

		if (null === $default) {
			$this->registerModifier('escape', fn($str) => $str);
		}
		else {
			$this->registerModifier('escape', fn ($str) => htmlspecialchars((string)$str) );
		}
	}

	public function registerAll()
	{
		// Register default Brindille modifiers
		$this->registerDefaults();

		// Common modifiers
		foreach (CommonModifiers::MODIFIERS_LIST as $key => $name) {
			$this->registerModifier(is_int($key) ? $name : $key, is_int($key) ? [CommonModifiers::class, $name] : $name);
		}

		foreach (CommonFunctions::FUNCTIONS_LIST as $key => $name) {
			$this->registerFunction(is_int($key) ? $name : $key, is_int($key) ? [CommonFunctions::class, $name] : $name);
		}

		// PHP modifiers
		foreach (CommonModifiers::PHP_MODIFIERS_LIST as $name => $params) {
			$this->registerModifier($name, [CommonModifiers::class, $name]);
		}

		// Local modifiers
		foreach (Modifiers::MODIFIERS_LIST as $key => $name) {
			$this->registerModifier(is_int($key) ? $name : $key, is_int($key) ? [Modifiers::class, $name] : $name);
		}

		foreach (Modifiers::MODIFIERS_WITH_INSTANCE_LIST as $key => $name) {
			$this->registerModifier(is_int($key) ? $name : $key, is_int($key) ? [Modifiers::class, $name] : $name, true);
		}

		// Local functions
		foreach (Functions::FUNCTIONS_LIST as $name) {
			$this->registerFunction($name, [Functions::class, $name]);
		}

		foreach (Functions::COMPILE_FUNCTIONS_LIST as $name => $callback) {
			$this->registerCompileBlock($name, $callback);
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
	public function setLocalSource(File $file)
	{
		if ($file->type != $file::TYPE_FILE) {
			throw new \LogicException('Cannot construct a UserTemplate with a directory');
		}

		$this->file = $file;
		$this->modified = $file->modified->getTimestamp();

		$this->cache_path = USER_TEMPLATES_CACHE_ROOT;
	}

	/**
	 * Load template code from a filesystem file
	 */
	public function setSource(string $path)
	{
		if (!($this->modified = @filemtime($path))) {
			throw new \InvalidArgumentException('File not found: ' . $path);
		}

		$this->file = null;
		$this->path = $path;

		// Use shared cache for default (DIST) templates
		$this->cache_path = SHARED_USER_TEMPLATES_CACHE_ROOT;
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

	public function display(): void
	{
		$compiled_path = $this->_getCachePath();

		try {
			$return = $this->displayUsingCache([$this, 'fetchCode'], $compiled_path, $this->modified);
		}
		catch (Brindille_Exception $e) {
			$path = $this->file ? $this->file->path : ($this->code ? 'code' : str_replace(ROOT, '…', $this->path));
			$is_user_code = $this->file || $this->code || ($this->module && $this->module->hasLocal());

			$message = sprintf("Erreur dans '%s' :\n%s", $path, $e->getMessage());

			if (!$is_user_code) {
				// We want errors in shipped code to be reported, it is not normal
				throw new \RuntimeException($message, 0, $e);
			}
			elseif (Session::getInstance()->isAdmin()) {
				// Report error to admin with the highlighted line
				$this->error($e, $message);
				return;
			}
			else {
				// Only report error
				throw new UserException($message, 0, $e);
			}
		}
		catch (\Throwable $e) {
			throw $e;
		}

		// If template returned 'STOP' string (eg. from the redirect function),
		// call exit now. Don't call exit in Brindille functions, or it might mess
		// with execution of other stuff, for example commitring a DB transaction
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
				throw new Brindille_Exception('Code HTTP inconnu: ' . $code);
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

		$content = $this->fetch();

		$this->dumpHeaders();

		if ($this->getContentType() == 'application/pdf') {
			Utils::streamPDF($content);
		}
		else {
			echo $content;
		}
	}

	/**
	 * Display custom error page for Brindille errors.
	 * Because we want to give the admin enough information on the issue
	 * so they can fix the issue in the code.
	 */
	public function error(\Exception $e, string $message)
	{
		// Fetch HTML error header from error_prepend_string (see ErrorManager)
		$header = ini_get('error_prepend_string');
		$header = preg_replace('!<if\((sent|logged|report|email|log)\)>(.*?)</if>!is', '', $header);
		echo $header;

		$name = strtok($this->_tpl_path, '/');
		strtok('');

		$path = $this->file->name ?? $this->path;
		$location = sprintf('Dans le code du module "%s"', $name);

		printf('<section><header><h1>%s</h1><h2>%s</h2></header>',
			$location, nl2br(htmlspecialchars($message)));

		if ($this->code || !preg_match('/Line (\d+)\s*:/i', $message, $match)) {
			return;
		}

		$line = $match[1] - 1;

		if ($this->file) {
			$file = explode("\n", $this->file->fetch());
		}
		else {
			$file = file($path);
		}

		$start = max(0, $line - 5);
		$max = min(count($file), $line + 6);

		echo '<pre><code>';

		for ($i = $start; $i < $max; $i++) {
			$code = sprintf('<b>%d</b>%s', $i + 1, htmlspecialchars($file[$i]));

			if ($i == $line) {
				$code = sprintf('<u>%s</u>', $code);
			}

			echo rtrim($code) . "\n";
		}

		echo '</code></pre>';
		exit;
	}

	public function _callFunction(string $name, array $params, int $line) {
		try {
			return call_user_func($this->_functions[$name], $params, $this, $line);
		}
		catch (UserException $e) {
			throw $e;
		}
		catch (\Exception $e) {
			throw new Brindille_Exception(sprintf("line %d: function '%s' has returned an error: %s\nParameters: %s", $line, $name, $e->getMessage(), substr(var_export($params, true), 6)), 0, $e);
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

		$table_name = 'module_data_' . $module->name;

		if (!DB::getInstance()->test('sqlite_master', 'type = \'table\' AND name = ?', $table_name)) {
			$table_name = null;
		}

		$this->module = $module;
		$this->assign('module', array_merge($module->asArray(false), [
			'config'       => json_decode(json_encode($module->config), true),
			'url'          => $module->url(),
			'public_url'   => $module->public_url(),
			'storage_root' => $module->storage_root(),
			'table'        => $table_name,
		]));
	}
}
