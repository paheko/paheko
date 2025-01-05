<?php

namespace Paheko;

use Paheko\Log;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;

use KD2\SMTP;
use KD2\Graphics\Image;
use KD2\I18N\TimeZones;

class Config extends Entity
{
	const FILES = [
		'admin_background' => File::CONTEXT_CONFIG . '/admin_bg.png',
		'admin_homepage'   => File::CONTEXT_CONFIG . '/admin_homepage.skriv',
		'admin_css'        => File::CONTEXT_CONFIG . '/admin.css',
		'logo'             => File::CONTEXT_CONFIG . '/logo.png',
		'icon'             => File::CONTEXT_CONFIG . '/icon.png',
		'favicon'          => File::CONTEXT_CONFIG . '/favicon.png',
		'signature'        => File::CONTEXT_CONFIG . '/signature.png',
	];

	const FILES_TYPES = [
		'admin_background' => 'image',
		'admin_css'        => 'code',
		'admin_homepage'   => 'web',
		'logo'             => 'image',
		'icon'             => 'image',
		'favicon'          => 'image',
		'signature'        => 'image',
	];

	/**
	 * List of config files that should be public no matter what
	 */
	const FILES_PUBLIC = [
		'logo', 'icon', 'favicon', 'admin_background', 'admin_css',
	];

	const VERSIONING_POLICIES = [
		'none' => [
			'label' => 'Ne pas conserver les anciennes versions',
			'help' => 'Permet d\'utiliser moins d\'espace disque',
		],
		'min' => [
			'label' => 'Conservation minimale',
			'help' => 'Jusqu\'à 5 versions seront conservées pour chaque fichier.',
			'intervals' => [
				// Keep one version only after 2 months
				-1 => INF,

				// First 10 minutes, one version
				600 => INF,

				// Next hour, one version
				3600 => INF,

				// Next 24h, one version
				3600*24 => INF,

				// Next 2 months, one version
				3600*24*60 => INF,
			],
		],
		'avg' => [
			'label' => 'Conservation moyenne',
			'help' => 'Jusqu\'à 20 versions seront conservées pour chaque fichier.',
			'intervals' => [
				// Keep one version after first 4 months
				-1 => INF,

				// First 10 minutes, one version every 5 minutes
				600 => 300,

				// Next hour, one version every 15 minutes
				3600 => 90,

				// Next 24h, one version every 3 hours
				3600*24 => 3*3600,

				// Next 4 months, one version per month
				3600*24*120 => 3600*24*30,
			],
		],
		'max' => [
			'label' => 'Conservation maximale',
			'help' => 'Jusqu\'à 50 versions seront conservées pour chaque fichier.',
			'intervals' => [
				//ends_after => step (interval size)
				// Keep one version each trimester after first 2 months
				-1 => 3600*24*30,

				// First 10 minutes, one version every 1 minute
				600 => 60,

				// Next hour, one version every 10 minutes
				3600 => 600,

				// Next 24h, one version every hour
				3600*24 => 3600,

				// Next 2 months, one version per week
				3600*24*60 => 3600*24*7,
			],
		],
	];

	protected string $org_name;
	protected ?string $org_infos;
	protected string $org_email;
	protected ?string $org_address;
	protected ?string $org_phone;
	protected ?string $org_web;

	protected string $currency;
	protected string $country;
	protected ?string $timezone = null;

	protected int $default_category;
	protected ?bool $show_parent_column = true;
	protected ?bool $show_has_children_column = true;

	protected ?int $backup_frequency;
	protected ?int $backup_limit;

	protected ?int $last_chart_change;
	protected ?string $last_version_check;

	protected ?string $color1;
	protected ?string $color2;

	protected array $files = [];

	protected bool $site_disabled;

	protected int $log_retention;
	protected bool $analytical_set_all = false;
	protected bool $analytical_mandatory = false;

	protected ?string $file_versioning_policy = null;
	protected int $file_versioning_max_size = 0;

	protected ?int $auto_logout = 0;

	static protected $_instance = null;

	static public function getInstance()
	{
		return self::$_instance ?: self::$_instance = new self;
	}

	static public function deleteInstance()
	{
		self::$_instance = null;
	}

	public function __clone()
	{
		throw new \LogicException('Cannot clone config');
	}

	protected function __construct()
	{
		parent::__construct();

		$db = DB::getInstance();

		$config = $db->getAssoc('SELECT key, value FROM config ORDER BY key;');

		if (empty($config)) {
			return;
		}

		$default = array_fill_keys(array_keys($this->_types), null);
		$config = array_merge($default, $config);

		foreach ($this->_types as $key => $type) {
			$value = $config[$key];

			if ($type[0] == '?' && $value === null) {
				continue;
			}
		}

		$this->load($config);
	}

	public function setCreateFlag(): void
	{
		foreach ($this->_types as $key => $t) {
			$this->_modified[$key] = null;
		}

		$this->files = array_map(fn($a) => null, self::FILES);
	}

	public function save(bool $selfcheck = true): bool
	{
		if (!count($this->_modified)) {
			return true;
		}

		if ($selfcheck) {
			$this->selfCheck();
		}

		$values = $this->getModifiedProperties();

		$db = DB::getInstance();
		$db->begin();

		foreach ($values as $key => $value)
		{
			$value = $this->getAsString($key);
			$db->preparedQuery('INSERT OR REPLACE INTO config (key, value) VALUES (?, ?);', $key, $value);
		}

		$db->commit();

		$this->_modified = [];

		if (array_key_exists('log_retention', $values)) {
			Log::clean();
		}

		return true;
	}

	public function delete(): bool
	{
		throw new \LogicException('Cannot delete config');
	}

	public function importForm(?array $source = null): void
	{
		$source ??= $_POST;

		if (!empty($source['show_parent_column_present']) && empty($source['show_parent_column'])) {
			$source['show_parent_column'] = false;
		}

		if (!empty($source['show_has_children_column_present']) && empty($source['show_has_children_column'])) {
			$source['show_has_children_column'] = false;
		}

		// N'enregistrer les couleurs que si ce ne sont pas les couleurs par défaut
		if (isset($source['color1'], $source['color2'])
			&& ($source['color1'] == ADMIN_COLOR1 && $source['color2'] == ADMIN_COLOR2))
		{
			$source['color1'] = null;
			$source['color2'] = null;
		}

		parent::importForm($source);
	}

	public function selfCheck(): void
	{
		$this->assert(trim($this->org_name) != '', 'Le nom de l\'association ne peut rester vide.');
		$this->assert(trim($this->currency) != '', 'La monnaie ne peut rester vide.');
		$this->assert(trim($this->country) != '' && Utils::getCountryName($this->country), 'Le pays ne peut rester vide.');
		$this->assert(!isset($this->org_web) || Utils::validateURL($this->org_web), 'L\'adresse URL du site web est invalide.');
		$this->assert(trim($this->org_email) != '' && SMTP::checkEmailIsValid($this->org_email, false), 'L\'adresse e-mail de l\'association est  invalide.');

		$this->assert($this->log_retention >= 0, 'La durée de rétention doit être égale ou supérieur à zéro.');

		// Files
		$this->assert(count($this->files) == count(self::FILES));

		foreach ($this->files as $key => $value) {
			$this->assert(array_key_exists($key, self::FILES));
			$this->assert(is_int($value) || is_null($value));
		}

		$db = DB::getInstance();
		$this->assert($db->test('users_categories', 'id = ?', $this->default_category), 'Catégorie de membres inconnue');

		$tzlist = TimeZones::listForCountry($this->country);

		// Make sure we set a valid timezone
		if (!array_key_exists($this->timezone, $tzlist)) {
			$this->set('timezone', key($tzlist));
		}

		$this->assert(!isset($this->color1) || preg_match('/^#[a-f0-9]{6}$/i', $this->color1));
		$this->assert(!isset($this->color2) || preg_match('/^#[a-f0-9]{6}$/i', $this->color2));
	}

	public function getSiteURL(): ?string
	{
		if ($this->site_disabled && $this->org_web) {
			return $this->org_web;
		}
		elseif ($this->site_disabled) {
			return null;
		}

		return WWW_URL;
	}

	public function file(string $key): ?File
	{
		if (!isset(self::FILES[$key])) {
			throw new \InvalidArgumentException('Invalid file key: ' . $key);
		}

		if (empty($this->files[$key])) {
			return null;
		}

		return Files::get(self::FILES[$key]);
	}

	public function fileURL(string $key, ?string $thumb_size = null): ?string
	{
		if (empty($this->files[$key])) {
			if ($key == 'favicon') {
				return ADMIN_URL . 'static/favicon.png';
			}
			elseif ($key == 'icon') {
				return ADMIN_URL . 'static/icon.png';
			}

			return null;
		}

		$url = WWW_URI . self::FILES[$key];

		if ($thumb_size) {
			$url .= '.' . $thumb_size . '.webp';
		}

		$url .= '?h=' . substr(md5($this->files[$key]), 0, 10);
		return $url;
	}


	public function hasFile(string $key): bool
	{
		return $this->files[$key] ? true : false;
	}

	public function updateFiles(): void
	{
		$files = $this->files;

		foreach (self::FILES as $key => $path) {
			if ($f = Files::get($path)) {
				$files[$key] = $f->modified->getTimestamp();
			}
			else {
				$files[$key] = null;
			}
		}

		$this->set('files', $files);
	}

	public function setFile(string $key, ?string $value, bool $upload = false): ?File
	{
		$f = Files::get(self::FILES[$key]);
		$files = $this->files;
		$type = self::FILES_TYPES[$key];
		$path = self::FILES[$key];

		// NULL = delete file
		if (null === $value) {
			if ($f) {
				$f->delete();
			}

			$f = null;
		}
		elseif ($upload) {
			$f = Files::upload(Utils::dirname($path), $value, Utils::basename($path));

			if ($type === 'image' && !$f->image) {
				$this->setFile($key, null);
				throw new UserException('Le fichier n\'est pas une image.');
			}

			try {
				// Force favicon format
				if ($key === 'favicon') {
					$format = 'png';
					$i = $f->asImageObject();
					$i->cropResize(32, 32);
					$f->setContent($i->output($format, true));
				}
				// Force icon format
				else if ($key === 'icon') {
					$format = 'png';
					$i = $f->asImageObject();
					$i->cropResize(512, 512);
					$f->setContent($i->output($format, true));
				}
				// Force signature size
				else if ($key === 'signature') {
					$format = 'png';
					$i = $f->asImageObject();
					$i->resize(200, 200);
					$f->setContent($i->output($format, true));
				}
			}
			catch (\UnexpectedValueException $e) {
				throw new UserException('Cet format d\'image n\'est pas supporté.', 0, $e);
			}
		}
		elseif ($f) {
			$f->setContent($value);
		}
		else {
			$f = Files::createFromString($path, $value);
		}

		$files[$key] = $f ? $f->modified->getTimestamp() : null;
		$this->set('files', $files);

		return $f;
	}
}
