<?php

namespace Garradin\Membres;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Champs
{
	protected $champs = null;

	protected $types = [
		'email'		=>	'Adresse E-Mail',
		'url'		=>	'Adresse URL',
		'checkbox'	=>	'Case à cocher',
		'date'		=>	'Date',
		'datetime'	=>	'Date et heure',
		//'file'		=>	'Fichier',
        'password'  =>  'Mot de passe',
		'number'	=>	'Numéro',
		'tel'		=>	'Numéro de téléphone',
		'select'	=>	'Sélecteur à choix unique',
        'multiple'  =>  'Sélecteur à choix multiple',
		'country'	=>	'Sélecteur de pays',
		'text'		=>	'Texte',
		'textarea'	=>	'Texte multi-lignes',
	];

    protected $text_types = [
        'email',
        'text',
        'select',
        'textarea',
        'url',
        'password',
        'country'
    ];

    protected $config_fields = [
        'type',
        'title',
        'help',
        'editable',
        'list_row',
        'mandatory',
        'private',
        'options'
    ];

    static protected $presets = null;

	public function __toString()
	{
		return Utils::write_ini_string($this->champs);
	}

    public function toString()
    {
        return Utils::write_ini_string($this->champs);
    }

	static public function importInstall()
	{
		$champs = parse_ini_file(\Garradin\ROOT . '/include/data/champs_membres.ini', true);
        $champs = array_filter($champs, function ($row) { return !empty($row['install']); });
        return new \Garradin\Membres\Champs($champs);
	}

    static public function importPresets()
    {
        if (is_null(self::$presets))
        {
            self::$presets = parse_ini_file(\Garradin\ROOT . '/include/data/champs_membres.ini', true);
        }

        return self::$presets;
    }

    static public function listUnusedPresets(Champs $champs)
    {
        return array_diff_key(self::importPresets(), $champs->getAll());
    }

	public function __construct($champs)
	{
		if ($champs instanceOf Champs)
		{
			$this->champs = $champs->getAll();
		}
        elseif (is_array($champs))
        {
            foreach ($champs as $key=>&$config)
            {
                $this->_checkField($key, $config);
            }

            $this->champs = $champs;
        }
		else
		{
			$champs = parse_ini_string((string)$champs, true);

            foreach ($champs as $key=>&$config)
            {
                $this->_checkField($key, $config);
            }

            $this->champs = $champs;
		}
	}

	public function getTypes()
	{
		return $this->types;
	}

	public function get($champ, $key = null)
	{
        if ($champ == 'id')
        {
            return ['title' => 'Numéro unique', 'type' => 'number'];
        }

        if (!array_key_exists($champ, $this->champs))
            return null;

        if ($key !== null)
        {
            if (array_key_exists($key, $this->champs[$champ]))
                return $this->champs[$champ][$key];
            else
                return null;
        }

		return $this->champs[$champ];
	}

    public function isText($champ)
    {
        if (!array_key_exists($champ, $this->champs))
            return null;

        if (in_array($this->champs[$champ]['type'], $this->text_types))
            return true;
        else
            return false;
    }

	public function getAll()
	{
        $this->champs['passe']['title'] = 'Mot de passe';
		return $this->champs;
	}

    public function getList()
    {
        $champs = $this->champs;
        unset($champs['passe']);
        return $champs;
    }

    public function getFirst()
    {
        reset($this->champs);
        return key($this->champs);
    }

    public function getListedFields()
    {
        $champs = $this->champs;

        $champs = array_filter($champs, function ($a) {
            return empty($a['list_row']) ? false : true;
        });

        uasort($champs, function ($a, $b) {
            if ($a['list_row'] == $b['list_row'])
                return 0;

            return ($a['list_row'] > $b['list_row']) ? 1 : -1;
        });

        return $champs;
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
            // Champ install non pris en compte
            if ($key == 'install')
            {
                unset($config[$key]);
                continue;
            }

            if (!in_array($key, $this->config_fields))
            {
                throw new \BadMethodCallException('Champ '.$key.' non valide.');
            }

            if ($key == 'editable' || $key == 'private' || $key == 'mandatory')
            {
                $value = (bool) (int) $value;
            }
            elseif ($key == 'list_row')
            {
                $value = (int) $value;
            }
            elseif ($key == 'help' || $key == 'title')
            {
                $value = trim((string) $value);
            }
            elseif ($key == 'options')
            {
                $value = (array) $value;

                foreach ($value as $option_key=>$option_value)
                {
                    if (trim($option_value) == '')
                    {
                        unset($value[$option_key]);
                    }
                }
            }
        }

        if (empty($config['title']) && $name != 'passe')
        {
            throw new UserException('Champ "'.$name.'" : Le titre est obligatoire.');
        }

        if (empty($config['type']) || !array_key_exists($config['type'], $this->types))
        {
            throw new UserException('Champ "'.$name.'" : Le type est vide ou non valide.');
        }

        if ($name == 'email' && $config['type'] != 'email')
        {
            throw new UserException('Le champ email ne peut être d\'un type différent de email.');
        }

        if ($name == 'passe' && $config['type'] != 'password')
        {
            throw new UserException('Le champ mot de passe ne peut être d\'un type différent de mot de passe.');
        }

        if (($config['type'] == 'multiple' || $config['type'] == 'select') && empty($config['options']))
        {
            throw new UserException('Le champ "'.$name.'" nécessite de comporter au moins une option possible.');
        }

        if (!array_key_exists('editable', $config))
        {
            $config['editable'] = false;
        }

        if (!array_key_exists('mandatory', $config))
        {
            $config['mandatory'] = false;
        }

        if (!array_key_exists('private', $config))
        {
            $config['private'] = false;
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
        if (!preg_match('!^[a-z]!', $name))
        {
            throw new UserException('Le nom du champ est invalide : le premier caractère doit être une lettre.');
        }
        
        if (!preg_match('!^[a-z][a-z0-9]*(_[a-z0-9]+)*$!', $name))
        {
            throw new UserException('Le nom du champ est invalide : ne sont acceptés que les lettres minuscules et les chiffres (éventuellement séparés par un underscore).');
        }
        
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
        if (!isset($this->champs[$champ]))
        {
            throw new \LogicException('Champ "'.$champ.'" inconnu.');
        }

        // Vérification
        $config = $this->champs[$champ];
        $config[$key] = $value;
        $this->_checkField($champ, $config);

		$this->champs[$champ] = $config;
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

        if (!array_key_exists('passe', $champs))
        {
            throw new UserException('Le champ Mot de passe ne peut être supprimé des fiches membres.');
        }

        $config = Config::getInstance();

        $identite = $config->get('champ_identite');

        if ($identite != 'id' && !array_key_exists($identite, $champs))
        {
            throw new UserException('Le champ '.$config->get('champ_identite')
                .' est défini comme identité des membres et ne peut donc être supprimé des fiches membres.');
        }

        $identifiant = $config->get('champ_identifiant');

        if ($identifiant != 'id' && !array_key_exists($identifiant, $champs))
        {
            throw new UserException('Le champ '.$config->get('champ_identifiant')
                .' est défini comme identifiant à la connexion et ne peut donc être supprimé des fiches membres.');
        }

        foreach ($champs as $name=>&$config)
        {
            $this->_checkField($name, $config);
        }

        $this->champs = $champs;

        return true;
    }

    /**
     * Enregistre les changements de champs en base de données
     * @param  boolean $enable_copy Recopier les anciennes champs dans les nouveaux ?
     * @return boolean true
     */
    public function save($enable_copy = true)
    {
    	$db = DB::getInstance();
    	$config = Config::getInstance();

    	// Champs à créer
    	$create = [
    		'id INTEGER PRIMARY KEY, -- Numéro attribué automatiquement',
    		'id_categorie INTEGER NOT NULL, -- Numéro de catégorie',
            'date_connexion TEXT NULL, -- Date de dernière connexion',
            'date_inscription TEXT NOT NULL DEFAULT CURRENT_DATE, -- Date d\'inscription',
    	];

        $create_keys = [
            'FOREIGN KEY (id_categorie) REFERENCES membres_categories (id)'
        ];

    	// Champs à recopier
    	$copy = [
    		'id',
    		'id_categorie',
            'date_connexion',
            'date_inscription',
    	];

        $anciens_champs = $config->get('champs_membres');
    	$anciens_champs = is_null($anciens_champs) ? $this->champs : $anciens_champs->getAll();

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

    		$line = $key . ' ' . $type . ',';

            if (!empty($cfg['title']))
            {
                $line .= ' -- ' . str_replace(["\n", "\r"], '', $cfg['title']);
            }

            $create[] = $line;

    		if (array_key_exists($key, $anciens_champs))
    		{
    			$copy[] = $key;
    		}
    	}

    	$create = array_merge($create, $create_keys);

    	$create = 'CREATE TABLE membres_tmp (' . "\n\t" . implode("\n\t", $create) . "\n);";
    	$copy = 'INSERT INTO membres_tmp (' . implode(', ', $copy) . ') SELECT ' . implode(', ', $copy) . ' FROM membres;';

    	$db->exec('PRAGMA foreign_keys = OFF;');
    	$db->exec('BEGIN;');
    	$db->exec($create);
    	
    	if ($enable_copy) {
    		$db->exec($copy);
    	}
    	
        $db->exec('DROP TABLE IF EXISTS membres;');
    	$db->exec('ALTER TABLE membres_tmp RENAME TO membres;');
        $db->exec('CREATE INDEX membres_id_categorie ON membres (id_categorie);'); // Index

        if ($config->get('champ_identifiant'))
        {
            // Mettre les champs identifiant vides à NULL pour pouvoir créer un index unique
            $db->exec('UPDATE membres SET '.$config->get('champ_identifiant').' = NULL 
                WHERE '.$config->get('champ_identifiant').' = "";');

            // Création de l'index unique
            $db->exec('CREATE UNIQUE INDEX membres_identifiant ON membres ('.$config->get('champ_identifiant').');');
        }

        // Création des index pour les champs affichés dans la liste des membres
        $listed_fields = array_keys($this->getListedFields());
        foreach ($listed_fields as $field)
        {
            if ($field === $config->get('champ_identifiant'))
            {
                // Il y a déjà un index
                continue;
            }

            $db->exec('CREATE INDEX membres_liste_' . $field . ' ON membres (' . $field . ');');
        }

    	$db->exec('END;');
    	$db->exec('PRAGMA foreign_keys = ON;');

    	$config->set('champs_membres', $this);
    	$config->save();

    	return true;
    }
}