<?php

namespace Garradin\UserTemplate;

use KD2\Brindille;
use KD2\Brindille_Exception;
use KD2\Translate;

use Garradin\Config;
use Garradin\Plugin;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\Users\Session;

use Garradin\Entities\Files\File;
use Garradin\Files\Files;

use Garradin\UserTemplate\Modifiers;
use Garradin\UserTemplate\Functions;
use Garradin\UserTemplate\Sections;

use Garradin\Web\Cache as Web_Cache;

use const Garradin\{WWW_URL, ADMIN_URL, SHARED_USER_TEMPLATES_CACHE_ROOT, USER_TEMPLATES_CACHE_ROOT, DATA_ROOT, ROOT, LEGAL_LINE};

class UserTemplate extends \KD2\Brindille
{
	const DIST_ROOT = ROOT . '/skel-dist/';

	public $_tpl_path;
	protected $content_type = null;
	protected $modified;
	protected $file = null;
	protected $code = null;
	protected $cache_path = USER_TEMPLATES_CACHE_ROOT;

	protected $escape_default = 'html';

	static protected $root_variables;

	static public function getRootVariables()
	{
		if (null !== self::$root_variables) {
			return self::$root_variables;
		}

		static $keys = ['color1', 'color2', 'org_name', 'org_address', 'org_email', 'org_phone', 'org_web', 'currency', 'country', 'files'];

		$config = Config::getInstance();

		$files = $config::FILES;

		// Put URL in files array
		array_walk($files, function (&$v, $k) use ($config) {
			$v = $config->fileURL($k);
		});

		$config = array_intersect_key($config->asArray(), array_flip($keys));
		$config['files'] = $files;

		// @deprecated
		// FIXME: remove in a future version
		$config['nom_asso'] = $config['org_name'];
		$config['adresse_asso'] = $config['org_address'];
		$config['email_asso'] = $config['org_email'];
		$config['telephone_asso'] = $config['org_phone'];
		$config['site_asso'] = $config['org_web'];

		$session = Session::getInstance();
		$is_logged = $session->isLogged();

		self::$root_variables = [
			'version_hash' => Utils::getVersionHash(),
			'root_url'     => WWW_URL,
			'request_url'  => Utils::getRequestURI(),
			'admin_url'    => ADMIN_URL,
			'_GET'         => &$_GET,
			'_POST'        => &$_POST,
			'visitor_lang' => Translate::getHttpLang(),
			'config'       => $config,
			'now'          => new \DateTime,
			'legal_line'   => LEGAL_LINE,
			'is_logged'    => $is_logged,
			'logged_user'  => $is_logged ? $session->getUser() : null,
		];

		return self::$root_variables;
	}

	public function __construct(string $path)
	{
		$this->_tpl_path = $path;

		if ($file = Files::get(File::CONTEXT_SKELETON . '/' . $path)) {
			if ($file->type != $file::TYPE_FILE) {
				throw new \LogicException('Cannot construct a UserTemplate with a directory');
			}

			$this->file = $file;
			$this->modified = $file->modified->getTimestamp();
		}
		else {
			$this->path = self::DIST_ROOT . $path;

			if (!($this->modified = @filemtime($this->path))) {
				throw new \InvalidArgumentException('File not found: ' . $this->path);
			}
		}

		$this->assignArray(self::getRootVariables());

		$this->registerAll();

		Plugin::fireSignal('usertemplate.init', ['template' => $this]);
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
			$this->_functions = [];
			$this->_sections = [];

			// Register default Brindille modifiers
			$this->registerDefaults();
		}
		else {
			$this->registerAll();
		}
	}

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
		foreach (Modifiers::PHP_MODIFIERS_LIST as $name) {
			$this->registerModifier($name, [Modifiers::class, $name]);
		}

		// Local modifiers
		foreach (Modifiers::MODIFIERS_LIST as $key => $name) {
			$this->registerModifier(is_int($key) ? $name : $key, is_int($key) ? [Modifiers::class, $name] : $name);
		}

		// Local functions
		foreach (Functions::FUNCTIONS_LIST as $name) {
			$this->registerFunction($name, [Functions::class, $name]);
		}

		// Local sections
		foreach (Sections::SECTIONS_LIST as $name) {
			$this->registerSection($name, [Sections::class, $name]);
		}

		$this->registerCompileBlock(':break', function (string $name, string $params, Brindille $tpl, int $line) {
			$in_loop = false;
			foreach ($this->_stack as $element) {
				if ($element[0] == $this::SECTION) {
					$in_loop = true;
					break;
				}
			}

			if (!$in_loop) {
				throw new Brindille_Exception(sprintf('Error on line %d: break can only be used inside a section', $line));
			}

			return '<?php break; ?>';
		});
	}

	public function setSource(string $path)
	{
		$this->file = null;
		$this->path = $path;
		$this->modified = filemtime($path);
		// Use shared cache for default templates
		$this->cache_path = SHARED_USER_TEMPLATES_CACHE_ROOT;
	}

	public function setCode(string $code)
	{
		$this->code = $code;
		$this->file = null;
		$this->path = null;
		$this->modified = time();
		// Use custom cache for user templates
		$this->cache_path = USER_TEMPLATES_CACHE_ROOT;
	}

	protected function _getCachePath()
	{
		$hash = sha1($this->file ? $this->file->path : ($this->code ?: $this->path));
		return sprintf('%s/%s.php', $this->cache_path, $hash);
	}

	public function display(): void
	{
		$compiled_path = $this->_getCachePath();

		if (!is_dir(dirname($compiled_path))) {
			// Force cache directory mkdir
			Utils::safe_mkdir(dirname($compiled_path), 0777, true);
		}

		if (file_exists($compiled_path) && filemtime($compiled_path) >= $this->modified) {
			require $compiled_path;
			return;
		}

		$tmp_path = $compiled_path . '.tmp';

		if ($this->code) {
			$source = $this->code;
		}
		elseif ($this->file) {
			$source = $this->file->fetch();
		}
		else {
			$source = file_get_contents($this->path);
		}

		try {
			$code = $this->compile($source);
			file_put_contents($tmp_path, $code);

			require $tmp_path;
		}
		catch (Brindille_Exception $e) {
			$path = $this->file ? $this->file->name : ($this->code ? 'code' : Utils::basename($this->path));

			$message = sprintf("Erreur dans '%s' :\n%s", $path, $e->getMessage());

			if (0 === strpos($this->path ?? '', self::DIST_ROOT)) {
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
			// Don't delete temporary file as it can be used to debug
			throw $e;
		}

		if (!file_exists(Utils::dirname($compiled_path))) {
			Utils::safe_mkdir(Utils::dirname($compiled_path), 0777, true);
		}

		rename($tmp_path, $compiled_path);
	}

	public function fetch(): string
	{
		ob_start();
		$this->display();
		return ob_get_clean();
	}

	public function displayPDF(?string $filename = null): void
	{
		$html = $this->fetch();

		if (!$filename && preg_match('!<title>([^<]+)</title>!', $html, $match)) {
			$title = trim(strip_tags(html_entity_decode($match[1])));

			if ($title !== '') {
				$filename = $title . '.pdf';
			}
		}

		if ($filename) {
			header(sprintf('Content-Disposition: attachment; filename="%s"', Utils::safeFileName($filename)));
		}

		header('Content-type: application/pdf');
		Utils::streamPDF($html);
	}

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

	public function serve(?string $cache_as_uri = null): void
	{
		if (!self::isTemplate($this->path)) {
			throw new \InvalidArgumentException('Not a valid template file extension: ' . $this->path);
		}

		$content = $this->fetch();
		$type = null;

		$type = 'text/html';

		// When the header has already been defined by the template
		foreach (headers_list() as $header) {
			if (preg_match('/^Content-Type: ([\w-]+\/[\w-]+)$/', $header, $match)) {
				$type = $match[1];
				break;
			}
		}

		if ($type != 'text/html' || !empty($this->_variables[0]['nocache'])) {
			$cache_as_uri = null;
		}

		header(sprintf('Content-Type: %s;charset=utf-8', $type), true);

		if ($type == 'application/pdf') {
			Utils::streamPDF($content);
		}
		else {
			echo $content;
		}

		if (null !== $cache_as_uri) {
			Web_Cache::store($cache_as_uri, $content);
		}
	}

	public function error(\Exception $e, string $message)
	{
		$header = ini_get('error_prepend_string');
		$header = preg_replace('!<if\((sent|logged|report|email|log)\)>(.*?)</if>!is', '', $header);
		echo $header;

		$path = $this->file->name ?? $this->path;
		$location = false !== strpos($path, '/web/') ? 'Dans un squelette du site web' : 'Dans le code d\'un formulaire';

		printf('<section><header><h1>%s</h1><h2>%s</h2></header>',
			$location, nl2br(htmlspecialchars($message)));

		if ($this->code || !preg_match('/Line (\d+)\s*:/i', $message, $match)) {
			return;
		}

		$line = $match[1] - 1;

		$file = file($path);
		$start = max(0, $line - 5);
		$max = min(count($file), $line + 6);

		echo '<pre><code>';

		for ($i = $start; $i < $max; $i++) {
			$code = sprintf('<b>%d</b>%s', $i + 1, htmlspecialchars($file[$i]));

			if ($i == $line) {
				$code = sprintf('<u>%s</u>', $code);
			}

			echo $code;
		}

		echo '</code></pre>';
	}
}
