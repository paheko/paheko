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

	public function set($champ, $key, $value)
	{
		$this->champs[$champs][$key] = $value;
		return true;
	}

    public function setAll($champs)
    {
        if (!array_key_exists('email', $champs))
        {
            throw new UserException('Le champ E-Mail ne peut être supprimé des fiches membres.');
        }

        foreach ($champs as $key=>$config)
        {
        	if (empty($config['type']) || !array_key_exists($config['type'], $this->types))
	    	{
	    		throw new UserException('Le champ '.$key.' doit être d\'un type connu.');
	    	}

	    	if ($key == 'email')
	    	{
	    		if ($config['type'] != 'email')
	    		{
	    			throw new UserException('Le champ email ne peut être d\'un type différent de email.');
	    		}
	    	}
        }

        $this->champs = $champs;

        return true;
    }

    public function diff()
    {
    	$db = DB::getInstance();
    	//$config
    }

    public function save($copy = true)
    {
    	$db = DB::getInstance();
    	$config = Config::getInstance();

    	// Champs à créer
    	$create = array(
    		'id INTEGER PRIMARY KEY, -- Numéro attribué automatiquement',
    		'id_categorie INTEGER NOT NULL, -- Numéro de catégorie',
    		'passe TEXT NULL, -- Mot de passe',
    	);

    	// Champs à recopier
    	$copy = array(
    		'id',
    		'id_categorie',
    		'passe'
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