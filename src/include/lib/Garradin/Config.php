<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;
use Garradin\Membres\Champs;

use KD2\SMTP;

class Config extends Entity
{
	const ADMIN_BACKGROUND_FILENAME = File::CONTEXT_CONFIG . '/admin_bg.png';

	protected $nom_asso;
	protected $adresse_asso;
	protected $email_asso;
	protected $telephone_asso;
	protected $site_asso;

	protected $monnaie;
	protected $pays;

	protected $champs_membres;
	protected $categorie_membres;

	protected $admin_homepage;

	protected $frequence_sauvegardes;
	protected $nombre_sauvegardes;

	protected $champ_identifiant;
	protected $champ_identite;

	protected $last_chart_change;
	protected $last_version_check;

	protected $couleur1;
	protected $couleur2;

	protected $admin_background;

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

		'admin_homepage'        => '?string',

		'frequence_sauvegardes' => '?int',
		'nombre_sauvegardes'    => '?int',

		'champ_identifiant'     => 'string',
		'champ_identite'        => 'string',

		'last_chart_change'     => '?int',
		'last_version_check'    => '?string',

		'couleur1'              => '?string',
		'couleur2'              => '?string',
		'admin_background'      => '?string',

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

		$values = [];
		$db = DB::getInstance();

		foreach ($this->_modified as $key => $modified) {
			$value = $this->$key;
			$type = ltrim($this->_types[$key], '?');

			if ($type == Champs::class) {
				$value = $value->toString();
			}
			elseif (is_object($value)) {
				throw new \UnexpectedValueException('Unexpected object as value: ' . get_class($value));
			}

			$values[$key] = $value;
		}

		unset($value, $key, $modified);

		$db->begin();

		foreach ($values as $key => $value)
		{
			$db->preparedQuery('INSERT OR REPLACE INTO config (key, value) VALUES (?, ?);', $key, $value);
		}

		if (!empty($values['champ_identifiant']))
		{
			// Mettre les champs identifiant vides à NULL pour pouvoir créer un index unique
			$db->exec('UPDATE membres SET '.$this->get('champ_identifiant').' = NULL
				WHERE '.$this->get('champ_identifiant').' = "";');

			// Création de l'index unique / FIXME move to Champs
			$db->exec('DROP INDEX IF EXISTS users_id_field;');
			$db->exec('CREATE UNIQUE INDEX users_id_field ON membres ('.$this->get('champ_identifiant').');');
		}

		$db->commit();

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
		if (!isset($source['couleur1'], $source['couleur2'])
			|| ($source['couleur1'] == ADMIN_COLOR1 && $source['couleur2'] == ADMIN_COLOR2))
		{
			$source['couleur1'] = null;
			$source['couleur2'] = null;
		}

		if (isset($source['admin_background']) && trim($source['admin_background']) == 'RESET') {
			$source['admin_background'] = null;
		}
		elseif (isset($source['admin_background']) && strlen($source['admin_background'])) {
			$file = Files::get(self::ADMIN_BACKGROUND_FILENAME);

			if ($file) {
				$file->storeFromBase64($source['admin_background']);
			}
			else {
				$file = File::createFromBase64(Utils::dirname(self::ADMIN_BACKGROUND_FILENAME), Utils::basename(self::ADMIN_BACKGROUND_FILENAME), $source['admin_background']);
			}

			$source['admin_background'] = $file->path;
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
		$this->assert(strlen($this->admin_homepage) > 0, 'Page d\'accueil invalide');
		$this->assert($this->champs_membres instanceof Champs, 'Objet champs membres invalide');

		$champs = $this->champs_membres;

		$this->assert(!empty($champs->get($this->champ_identite)), sprintf('Le champ spécifié pour identité, "%s" n\'existe pas', $this->champ_identite));
		$this->assert(!empty($champs->get($this->champ_identifiant)), sprintf('Le champ spécifié pour identifiant, "%s" n\'existe pas', $this->champ_identifiant));

		$db = DB::getInstance();
		$sql = sprintf('SELECT (COUNT(DISTINCT LOWER(%s)) = COUNT(*)) FROM membres WHERE %1$s IS NOT NULL AND %1$s != \'\';', $this->champ_identifiant);
		$is_unique = $db->firstColumn($sql);

		$this->assert($is_unique, sprintf('Le champ "%s" comporte des doublons et ne peut donc pas servir comme identifiant unique de connexion.', $this->champ_identifiant));

		$this->assert($db->test('users_categories', 'id = ?', $this->categorie_membres), 'Catégorie de membres inconnue');
	}
}
