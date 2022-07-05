<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Membres\Champs;

use KD2\SMTP;
use KD2\Graphics\Image;

class Config extends Entity
{
	const FILES = [
		'admin_background' => File::CONTEXT_CONFIG . '/admin_bg.png',
		'admin_homepage'   => File::CONTEXT_CONFIG . '/admin_homepage.skriv',
		'admin_css'        => File::CONTEXT_CONFIG . '/admin.css',
		'logo'             => File::CONTEXT_CONFIG . '/logo.png',
		'icon'             => File::CONTEXT_CONFIG . '/icon.png',
		'favicon'          => File::CONTEXT_CONFIG . '/favicon.png',
	];

	const FILES_TYPES = [
		'admin_background' => 'image',
		'admin_css'        => 'code',
		'admin_homepage'   => 'web',
		'logo'             => 'image',
		'icon'             => 'image',
		'favicon'          => 'image',
	];

	protected $nom_asso;
	protected $adresse_asso;
	protected $email_asso;
	protected $telephone_asso;
	protected $site_asso;

	protected $monnaie;
	protected $pays;

	protected $champs_membres;
	protected $categorie_membres;

	protected $frequence_sauvegardes;
	protected $nombre_sauvegardes;

	protected $champ_identifiant;
	protected $champ_identite;

	protected $last_chart_change;
	protected $last_version_check;

	protected $couleur1;
	protected $couleur2;

	protected $files = [];

	protected $site_disabled;

	protected $_types = [
		'nom_asso'              => 'string',
		'adresse_asso'          => '?string',
		'email_asso'            => 'string',
		'telephone_asso'        => '?string',
		'site_asso'             => '?string',

		'monnaie'               => 'string',
		'pays'                  => 'string',

		'champs_membres'        => Champs::class,

		'categorie_membres'     => 'int',

		'frequence_sauvegardes' => '?int',
		'nombre_sauvegardes'    => '?int',

		'champ_identifiant'     => 'string',
		'champ_identite'        => 'string',

		'last_chart_change'     => '?int',
		'last_version_check'    => '?string',

		'couleur1'              => '?string',
		'couleur2'              => '?string',

		'files'                 => 'array',

		'site_disabled'         => 'bool',
	];

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

		$config['champs_membres'] = new Champs($config['champs_membres']);

		foreach ($this->_types as $key => $type) {
			$value = $config[$key];

			if ($type[0] == '?' && $value === null) {
				continue;
			}
		}

		$this->load($config);

		$this->champs_membres = new Membres\Champs((string)$this->champs_membres);
	}

	public function save(): bool
	{
		if (!count($this->_modified)) {
			return true;
		}

		$this->selfCheck();

		$values = $this->modifiedProperties(true);

		$db = DB::getInstance();
		$db->begin();

		foreach ($values as $key => $value)
		{
			$db->preparedQuery('INSERT OR REPLACE INTO config (key, value) VALUES (?, ?);', $key, $value);
		}

		if (!empty($values['champ_identifiant'])) {
			// Regenerate login index
			$db->exec('DROP INDEX IF EXISTS users_id_field;');
			$config = Config::getInstance();
			$champs = $config->get('champs_membres');
			$champs->createIndexes();
		}

		$db->commit();

		if (isset($values['couleur1']) || isset($values['couleur2'])) {
			// Reset graph cache
			Static_Cache::clean(0);
		}

		$this->_modified = [];

		return true;
	}

	public function delete(): bool
	{
		throw new \LogicException('Cannot delete config');
	}

	public function importForm($source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		// N'enregistrer les couleurs que si ce ne sont pas les couleurs par défaut
		if (isset($source['couleur1'], $source['couleur2'])
			&& ($source['couleur1'] == ADMIN_COLOR1 && $source['couleur2'] == ADMIN_COLOR2))
		{
			$source['couleur1'] = null;
			$source['couleur2'] = null;
		}

		parent::importForm($source);
	}

	protected function _filterType(string $key, $value)
	{
		switch ($this->_types[$key]) {
			case 'int':
				return (int) $value;
			case 'bool':
				return (bool) $value;
			case 'string':
				return (string) $value;
			case Champs::class:
				if (!is_object($value) || !($value instanceof $this->_types[$key])) {
					throw new \InvalidArgumentException(sprintf('"%s" is not of type "%s"', $key, $this->_types[$key]));
				}
				return $value;
			default:
				throw new \InvalidArgumentException(sprintf('"%s" has unknown type "%s"', $key, $this->_types[$key]));
		}
	}

	public function selfCheck(): void
	{
		$this->assert(trim($this->nom_asso) != '', 'Le nom de l\'association ne peut rester vide.');
		$this->assert(trim($this->monnaie) != '', 'La monnaie ne peut rester vide.');
		$this->assert(trim($this->pays) != '' && Utils::getCountryName($this->pays), 'Le pays ne peut rester vide.');
		$this->assert(null === $this->site_asso || filter_var($this->site_asso, FILTER_VALIDATE_URL), 'L\'adresse URL du site web est invalide.');
		$this->assert(trim($this->email_asso) != '' && SMTP::checkEmailIsValid($this->email_asso, false), 'L\'adresse e-mail de l\'association est  invalide.');
		$this->assert($this->champs_membres instanceof Champs, 'Objet champs membres invalide');

		// Files
		$this->assert(count($this->files) == count(self::FILES));

		foreach ($this->files as $key => $value) {
			$this->assert(array_key_exists($key, self::FILES));
			$this->assert(is_int($value) || is_null($value));
		}

		$champs = $this->champs_membres;

		$this->assert(!empty($champs->get($this->champ_identite)), sprintf('Le champ spécifié pour identité, "%s" n\'existe pas', $this->champ_identite));
		$this->assert(!empty($champs->get($this->champ_identifiant)), sprintf('Le champ spécifié pour identifiant, "%s" n\'existe pas', $this->champ_identifiant));

		$db = DB::getInstance();

		// Check that this field is actually unique
		if (isset($this->_modified['champ_identifiant'])) {
			$sql = sprintf('SELECT (COUNT(DISTINCT %s COLLATE U_NOCASE) = COUNT(*)) FROM membres WHERE %1$s IS NOT NULL AND %1$s != \'\';', $this->champ_identifiant);
			$is_unique = (bool) $db->firstColumn($sql);

			$this->assert($is_unique, sprintf('Le champ "%s" comporte des doublons et ne peut donc pas servir comme identifiant unique de connexion.', $this->champ_identifiant));
		}

		$this->assert($db->test('users_categories', 'id = ?', $this->categorie_membres), 'Catégorie de membres inconnue');
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

	public function fileURL(string $key, string $params = ''): ?string
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

		$params = $params ? $params . '&' : '';

		return BASE_URL . self::FILES[$key] . '?' . $params . substr(md5($this->files[$key]), 0, 10);
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
			$f = File::upload(Utils::dirname($path), $value, Utils::basename($path));

			if ($type == 'image' && !$f->image) {
				$this->setFile($key, null);
				throw new UserException('Le fichier n\'est pas une image.');
			}

			// Force favicon format
			if ($key == 'favicon') {
				$format = 'png';
				$i = new Image($f->fullpath());
				$i->cropResize(32, 32);
				$f->setContent($i->output($format, true));
			}
			// Force icon format
			else if ($key == 'favicon') {
				$format = 'png';
				$i = new Image($f->fullpath());
				$i->cropResize(512, 512);
				$f->setContent($i->output($format, true));
			}
		}
		elseif ($f) {
			$f->setContent($value);
		}
		else {
			$f = File::createAndStore(Utils::dirname($path), Utils::basename($path), null, $value);
		}

		$files[$key] = $f ? $f->modified->getTimestamp() : null;
		$this->set('files', $files);

		return $f;
	}
}
