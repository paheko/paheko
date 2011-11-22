<?php

class Garradin_Config
{
    protected $fields_types = null;
    protected $config = null;
    protected $modified = array();

    protected $allowed_mandatory_fields_membres = array('passe', 'nom', 'email', 'adresse', 'code_postal',
        'ville', 'pays', 'telephone', 'date_anniversaire', 'details');

    static protected $_instance = null;

    static public function getInstance()
    {
        return self::$_instance ?: self::$_instance = new Garradin_Config;
    }

    private function __clone()
    {
    }

    protected function __construct()
    {
        $string = '';
        $int = 0;
        $float = 0.0;
        $array = array();
        $bool = false;

        $this->fields_types = array(
            'nom_asso'              =>  $string,
            'adresse_asso'          =>  $string,
            'email_asso'            =>  $string,
            'site_asso'             =>  $string,

            'email_envoi_automatique'=> $string,

            'champs_obligatoires'   =>  $array,
            'categorie_membres'     =>  $int,

            'categorie_dons'        =>  $int,
            'categorie_cotisations' =>  $int,
        );

        $db = Garradin_DB::getInstance();

        $this->config = $db->simpleStatementFetchAssoc('SELECT cle, valeur FROM config ORDER BY cle;');

        foreach ($this->config as $key=>&$value)
        {
            if (!array_key_exists($key, $this->fields_types))
            {
                throw new OutOfBoundsException('Le champ "'.$key.'" est inconnu.');
            }

            if (is_array($this->fields_types[$key]))
            {
                $value = explode(',', $value);
            }
            else
            {
                settype($value, gettype($this->fields_types[$key]));
            }
        }
    }

    public function __destruct()
    {
        if (!empty($this->modified))
        {
            //echo '<div style="color: red; background: #fff;">Il y a des champs modifiés non sauvés dans '.__CLASS__.' !</div>';
        }
    }

    public function save()
    {
        if (empty($this->modified))
            return true;

        $values = array();

        $db = Garradin_DB::getInstance();
        $db->exec('BEGIN;');

        foreach ($this->modified as $key=>$modified)
        {
            $value = $this->config[$key];

            if (is_array($value))
            {
                $value = implode(',', $value);
            }

            $db->simpleExec('INSERT OR REPLACE INTO config (cle, valeur) VALUES (?, ?);',
                $key, $value);
        }

        $db->exec('END;');

        $this->modified = array();

        return true;
    }

    public function get($key)
    {
        if (!array_key_exists($key, $this->config))
        {
            throw new OutOfBoundsException('Ce champ est inconnu.');
        }

        return $this->config[$key];
    }

    public function set($key, $value)
    {
        if (!array_key_exists($key, $this->fields_types))
        {
            throw new OutOfBoundsException('Ce champ est inconnu.');
        }

        if (is_array($this->fields_types[$key]))
        {
            $value = (array) $value;
        }
        elseif (is_int($this->fields_types[$key]))
        {
            $value = (int) $value;
        }
        elseif (is_float($this->fields_types[$key]))
        {
            $value = (float) $value;
        }
        elseif (is_bool($this->fields_types[$key]))
        {
            $value = (bool) $value;
        }
        elseif (is_string($this->fields_types[$key]))
        {
            $value = (string) $value;
        }

        switch ($key)
        {
            case 'nom_asso':
            {
                if (!trim($value))
                {
                    throw new UserException('Le nom de l\'association ne peut rester vide.');
                }
                break;
            }
            case 'email_asso':
            case 'email_envoi_automatique':
            {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL))
                {
                    throw new UserException('Adresse e-mail invalide.');
                }
                break;
            }
            case 'champs_obligatoires':
            {
                foreach ($value as $name)
                {
                    if (!in_array($name, $this->allowed_mandatory_fields_membres))
                    {
                        throw new UserException('Le champ \''.$name.'\' ne peut pas être rendu obligatoire.');
                    }
                }
                break;
            }
            case 'categorie_cotisations':
            case 'categorie_dons':
            {
                $db = Garradin_DB::getInstance();
                if (!$db->simpleQuerySingle('SELECT 1 FROM compta_categories WHERE id = ?;', false, $value))
                {
                    throw new UserException('Champ '.$key.' : La catégorie comptable numéro \''.$value.'\' ne semble pas exister.');
                }
                break;
            }
            case 'categorie_membres':
            {
                $db = Garradin_DB::getInstance();
                if (!$db->simpleQuerySingle('SELECT 1 FROM membres_categories WHERE id = ?;', false, $value))
                {
                    throw new UserException('La catégorie de membres par défaut numéro \''.$value.'\' ne semble pas exister.');
                }
                break;
            }
            default:
                break;
        }

        if (!isset($this->config[$key]) || $value !== $this->config[$key])
        {
            $this->config[$key] = $value;
            $this->modified[$key] = true;
        }

        return true;
    }
}

?>