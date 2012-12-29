<?php

namespace Garradin;

class Champs_Membres
{
	protected $champs = null;

	protected $types = array(
		'email'		=>	'Adresse E-Mail',
		'url'		=>	'Adresse URL',
		'checkbox'	=>	'Case à cocher',
		'multiple'	=>	'Combinaison de cases à cocher',
		'date'		=>	'Date',
		'datetime'	=>	'Date et heure',
		'file'		=>	'Fichier',
		'number'	=>	'Numéro',
		'tel'		=>	'Numéro de téléphone',
		'select'	=>	'Sélecteur de choix',
		'country'	=>	'Sélecteur de pays',
		'text'		=>	'Texte',
		'textarea'	=>	'Texte multi-lignes',
	);

    protected $config_fields = array(
        'type',
        'title',
        'help',
        'editable',
        'mandatory',
        'private',
        'values'
    );

    static protected $presets = null;

	public function __toString()
	{
		return json_encode($this->champs);
	}

	static public function import()
	{
		$json = file_get_contents(GARRADIN_ROOT . '/include/data/champs_membres.json');
		$json = preg_replace('!/[*].*?[*]/!s', '', $json);
		return new Champs_Membres($json);
	}

    static public function importPresets()
    {
        if (is_null(self::$presets))
        {
            $json = file_get_contents(GARRADIN_ROOT . '/include/data/champs_membres.json');
            $json = preg_replace('!/[*].*?[*]/!s', '', $json);
            $champs = json_decode($json, true);

            $json = file_get_contents(GARRADIN_ROOT . '/include/data/champs_membres_supplementaires.json');
            $json = preg_replace('!/[*].*?[*]/!s', '', $json);
            $champs_supplementaires = json_decode($json, true);

            self::$presets = array_merge($champs, $champs_supplementaires);
        }

        return self::$presets;
    }

    static public function listUnusedPresets(Champs_Membres $champs)
    {
        return array_diff_key(self::importPresets(), $champs->getAll());
    }

	public function __construct($champs)
	{
		if ($champs instanceOf Champs_Membres)
		{
			$this->champs = $champs->getAll();
		}
		else
		{
			$this->champs = json_decode((string)$champs, true);
		}
	}

	public function getTypes()
	{
		return $this->types;
	}

	public function get($key)
	{
		return $this->champs[$key];
	}

	public function getAll()
	{
		return $this->champs;
	}

    /**
     * Vérifie la cohérence et la présence des bons éléments pour un champ
     * @param  string $name     Nom du champ
     * @param  array $config    Configuration du champ
     * @return boolean true
     */
    protected function _checkField($name, &$config)
    {
        if (!preg_match('!^\w+(_\w+)*$!', $name))
        {
            throw new UserException('Le nom du champ est invalide.');
        }

         foreach ($config as $key=>&$value)
        {
            if (!in_array($key, $this->config_fields))
            {
                throw new \BadMethodCallException('Champ '.$key.' non valide.');
            }

            if ($key == 'editable' || $key == 'private' || $key == 'mandatory')
            {
                $value = (bool) (int) $value;
            }
            elseif ($key == 'help' || $key == 'title')
            {
                $value = trim((string) $value);
            }
        }

        if (empty($config['title']))
        {
            throw new UserException('Le titre est obligatoire.');
        }

        if (empty($config['type']) || !array_key_exists($config['type'], $this->types))
        {
            throw new UserException('Le type est vide ou non valide.');
        }

        if ($name == 'email' && $config['type'] != 'email')
        {
            throw new UserException('Le champ email ne peut être d\'un type différent de email.');
        }

        return true;
    }

    /**
     * Ajouter un nouveau champ
     * @param string $name Nom du champ
     * @param array $config Configuration du champ
     * @return boolean true
     */
    public function add($name, $config)
    {
        $this->_checkField($name, $config);

        $this->champs[$name] = $config;

        return true;
    }

    /**
     * Modifie un champ particulier
     * @param string $champ Nom du champ
     * @param string $key   Nom de la clé à modifier
     * @param mixed  $value Valeur à affecter
     * @return boolean true
     */
	public function set($champ, $key, $value)
	{
		$this->champs[$champs][$key] = $value;
		return true;
	}

    /**
     * Modifie les champs en interne en vérifiant que tout va bien
     * @param array $champs Liste des champs
     * @return boolean true
     */
    public function setAll($champs)
    {
        if (!array_key_exists('email', $champs))
        {
            throw new UserException('Le champ E-Mail ne peut être supprimé des fiches membres.');
        }

        foreach ($champs as $name=>$config)
        {
            $this->_checkField($name, $config);
        }

        $this->champs = $champs;

        return true;
    }

    public function diff()
    {
    	$db = DB::getInstance();
    	//$config
    }

    /**
     * Enregistre les changements de champs en base de données
     * @param  boolean $copy Recopier les anciennes champs dans les nouveaux ?
     * @return boolean true
     */
    public function save($copy = true)
    {
    	$db = DB::getInstance();
    	$config = Config::getInstance();

    	// Champs à créer
    	$create = array(
    		'id INTEGER PRIMARY KEY, -- Numéro attribué automatiquement',
    		'id_categorie INTEGER NOT NULL, -- Numéro de catégorie',
    		'passe TEXT NULL, -- Mot de passe',
            'date_connexion TEXT NULL, -- Date de dernière connexion',
    	);

    	// Champs à recopier
    	$copy = array(
    		'id',
    		'id_categorie',
    		'passe',
            'date_connexion'
    	);

    	$anciens_champs = $config->get('champs_membres')->getAll();

    	foreach ($this->champs as $key=>$cfg)
    	{
    		if ($cfg['type'] == 'number')
    			$type = 'FLOAT';
    		elseif ($cfg['type'] == 'multiple' || $cfg['type'] == 'checkbox')
    			$type = 'INTEGER';
    		elseif ($cfg['type'] == 'file')
    			$type = 'BLOB';
    		else
    			$type = 'TEXT';

    		$create[] = $key . ' ' . $type . ' ' . ', -- ' . str_replace(array("\n", "\r"), '', $cfg['title']);

    		if (array_key_exists($key, $anciens_champs))
    		{
    			$copy[] = $key;
    		}
    	}

    	$last = count($create) - 1;
    	$create[$last] = str_replace(',', '', $create[$last]);

    	$create = 'CREATE TABLE membres_tmp (' . "\n\t" . implode("\n\t", $create) . "\n);";
    	$copy = 'INSERT INTO membres_tmp (' . implode(', ', $copy) . ') SELECT ' . implode(', ', $copy) . ' FROM membres;';

    	$db->exec('PRAGMA foreign_keys = OFF;');
    	$db->exec('BEGIN;');
    	$db->exec($create);
    	
    	if ($copy) {
    		$db->exec($copy);
    	}
    	
    	$db->exec('DROP TABLE membres;');
    	$db->exec('ALTER TABLE membres_tmp RENAME TO membres;');
    	$db->exec('END;');
    	$db->exec('PRAGMA foreign_keys = ON;');

    	$config->set('champs_membres', $this);
    	$config->save();

    	return true;
    }
}

?>