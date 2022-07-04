<?php

namespace Garradin\UserTemplate;

use KD2\Brindille;
use KD2\Brindille_Exception;
use KD2\Translate;

use Garradin\Config;
use Garradin\Plugin;
use Garradin\Utils;

use Garradin\Membres\Session;

use Garradin\Web\Skeleton;
use Garradin\Entities\Files\File;

use Garradin\UserTemplate\Modifiers;
use Garradin\UserTemplate\Functions;
use Garradin\UserTemplate\Sections;

use const Garradin\{WWW_URL, ADMIN_URL, SHARED_USER_TEMPLATES_CACHE_ROOT, USER_TEMPLATES_CACHE_ROOT, DATA_ROOT};

class UserTemplate extends Brindille
{
	protected $path = null;
	protected $modified;
	protected $file = null;
	protected $code = null;
	protected $cache_path = USER_TEMPLATES_CACHE_ROOT;

	protected $escape_default = 'html';

	static protected $root_variables;

	protected $content_type = null;

	static public function getRootVariables()
	{
		if (null !== self::$root_variables) {
			return self::$root_variables;
		}

		static $keys = ['adresse_asso', 'champ_identifiant', 'champ_identite', 'couleur1', 'couleur2', 'email_asso', 'monnaie', 'nom_asso', 'pays', 'site_asso', 'telephone_asso', 'files'];

		$config = Config::getInstance();

		$files = $config::FILES;

		// Put URL in files array
		array_walk($files, function (&$v, $k) use ($config) {
			$v = $config->fileURL($k);
		});

		$config = array_intersect_key($config->asArray(), array_flip($keys));
		$config['files'] = $files;

		$session = Session::getInstance();
		$is_logged = $session->isLogged();

		self::$root_variables = [
			'root_url'     => WWW_URL,
			'request_url'  => Utils::getRequestURI(),
			'admin_url'    => ADMIN_URL,
			'_GET'         => &$_GET,
			'_POST'        => &$_POST,
			'visitor_lang' => Translate::getHttpLang(),
			'config'       => $config,
			'is_logged'    => $is_logged,
			'logged_user'  => $is_logged && $session->getUser(),
		];

		return self::$root_variables;
	}

	public function __construct(?File $file = null)
	{
		if ($file) {
			$this->file = $file;
			$this->modified = $file->modified->getTimestamp();
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

		foreach (CommonModifiers::FUNCTIONS_LIST as $key => $name) {
			$this->registerFunction(is_int($key) ? $name : $key, is_int($key) ? [CommonModifiers::class, $name] : $name);
		}

		// PHP modifiers
		foreach (Modifiers::PHP_MODIFIERS_LIST as $name) {
			$this->registerModifier($name, $name);
		}

		// Local modifiers
		foreach (Modifiers::MODIFIERS_LIST as $name) {
			$this->registerModifier($name, [Modifiers::class, $name]);
		}

		// Local functions
		foreach (Functions::FUNCTIONS_LIST as $name) {
			$this->registerFunction($name, [Functions::class, $name]);
		}

		// Local sections
		foreach (Sections::SECTIONS_LIST as $name) {
			$this->registerSection($name, [Sections::class, $name]);
		}

		$this->registerModifier('money', function ($number, bool $hide_empty = true, bool $force_sign = false): string {
			if ($hide_empty && !$number) {
				return '';
			}

			$sign = ($force_sign && $number > 0) ? '+' : '';

			$out = $sign . Utils::money_format($number, ',', '.', $hide_empty);

			if (!$this->escape_default) {
				return $out;
			}

			return sprintf('<b class="money">%s</b>', str_replace('.', '&nbsp;', $out));
		});

		$this->registerModifier('money_currency', function ($number, bool $hide_empty = true): string {
			$out = $this->_modifiers['money']($number, $hide_empty);

			if ($out !== '') {
				$out .= $this->escape_default == 'html' ? '&nbsp;' : ' ';
				$out .= Config::getInstance()->get('monnaie');
			}

			return $out;
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
		$compiled_path = $this->_getCachePath(true);

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
			throw new Brindille_Exception(sprintf("Erreur de syntaxe dans '%s' : %s",
				$this->file ? $this->file->name : ($this->code ? 'code' : Utils::basename($this->path)),
				$e->getMessage()), 0, $e);
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
		header('Content-type: application/pdf');

		if ($filename) {
			header(sprintf('Content-Disposition: attachment; filename="%s"', Utils::safeFileName($filename)));
		}

		Utils::streamPDF($this->fetch());
	}

	public function setContentType(string $type): void
	{
		$this->content_type = $type;
	}

	public function displayWeb(): void
	{
		$content = $this->fetch();

		$type = $this->content_type ?: 'text/html';
		header(sprintf('Content-Type: %s;charset=utf-8', $type), true);

		if ($type == 'application/pdf') {
			Utils::streamPDF($content);
		}
		else {
			echo $content;
		}
	}
}
