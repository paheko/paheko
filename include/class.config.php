<?php

class Garradin_Config
{
    protected $fields_types = null;
    protected $config = null;
    protected $modified = false;

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

        $this->config = $db->queryAssociativeFetch('SELECT cle, valeur FROM config ORDER BY cle;');

        foreach ($this->config as $key=>&$value)
        {
            if (!array_key_exists($key, $this->fields_types))
            {
                throw new OutOfBoundsException('Le champ "'.$key.'" est inconnu.');
            }

            if (is_array($this->fields_types[$key]))
            {
                $value = json_decode($value, true);
            }
            else
            {
                settype($value, gettype($this->fields_types[$key]));
            }
        }
    }

    public function __destruct()
    {
    }

    public function save()
    {
        $values = $config;
        // serialization des valeurs (floatval, etc.) + SQL query
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

        if ($value !== $this->config[$key])
        {
            $this->config[$key] = $value;
            $this->modified = true;
        }

        return true;
    }
}

?>