<?php

namespace Garradin\Membres;

use Garradin\Config;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;

class Champs
{
    const TABLE = 'membres';

	protected $champs = null;

    protected $system_fields = [
        'date_connexion',
        'date_inscription',
        'clef_pgp',
        'secret_otp',
        'id',
        'id_category',
    ];

	protected $types = [
		'email'		=>	'Adresse E-Mail',
		'url'		=>	'Adresse URL',
		'checkbox'	=>	'Case à cocher',
		'date'		=>	'Date',
		'datetime'	=>	'Date et heure',
        'file'      =>  'Fichier',
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
        return new \Garradin\Membres\Champs($champs, true);
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
        return array_diff_key(self::importPresets(), (array) $champs->getAll());
    }

	public function __construct($champs, $initial_setup = false)
	{
		if ($champs instanceOf Champs)
		{
			$this->champs = $champs->getAll();
		}
        elseif (is_array($champs))
        {
            $this->setAll($champs, $initial_setup);
        }
		else
		{
			$champs = parse_ini_string((string)$champs, true);

            foreach ($champs as $key=>&$config)
            {
                $config = (object) $config;
                $this->_checkField($key, $config);
            }

            $this->champs = (object) $champs;
		}
	}

	public function getTypes()
	{
		return $this->types;
	}

	public function get($champ, $key = null)
	{
        if (!property_exists($this->champs, $champ))
            return null;

        if ($key !== null)
        {
            if (property_exists($this->champs->$champ, $key))
                return $this->champs->$champ->$key;
            else
                return null;
        }

		return $this->champs->$champ;
	}

    public function isText($champ)
    {
        if (!property_exists($this->champs, $champ))
            return null;

        if (in_array($this->champs->$champ->type, $this->text_types))
            return true;
        else
            return false;
    }

    public function getKeys($all = false)
    {
        $keys = [];

        foreach ($this->champs as $key => $config)
        {
            if (!$all && $key == 'passe')
            {
                continue;
            }

            $keys[] = $key;
        }

        return $keys;
    }

	public function getAll()
	{
		return $this->champs;
	}

    public function getList()
    {
        $champs = clone $this->champs;
        unset($champs->passe);

        return $champs;
    }

    public function listAssocNames()
    {
        $out = [];

        foreach ($this->champs as $key => $config) {
            if ($key == 'passe') {
                continue;
            }

            $out[$key] = $config->title;
        }

        return $out;
    }

    public function getMultiples()
    {
        $out = [];

        foreach ($this->champs as $id => $champ) {
            if ($champ->type == 'multiple') {
                $out[$id] = $champ;
            }
        }

        return $out;
    }

    public function getListedFields()
    {
        $champs = (array) $this->champs;

        $champs = array_filter($champs, function ($a) {
            return empty($a->list_row) ? false : true;
        });

        uasort($champs, function ($a, $b) {
            if ($a->list_row == $b->list_row)
                return 0;

            return ($a->list_row > $b->list_row) ? 1 : -1;
        });

        return (object) $champs;
    }

    public function getFirstListed()
    {
        foreach ($this->champs as $key=>$config)
        {
            if (empty($config->list_row))
            {
                continue;
            }

            return $key;
        }
    }

    /**
     * Vérifie la cohérence et la présence des bons éléments pour un champ
     * @param  string $name     Nom du champ
     * @param  array $config    Configuration du champ
     * @return boolean true
     */
    protected function _checkField($name, \stdClass &$config)
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
                unset($config->$key);
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

        if (empty($config->title) && $name != 'passe')
        {
            throw new UserException('Champ "'.$name.'" : Le titre est obligatoire.');
        }

        if (empty($config->type) || !array_key_exists($config->type, $this->types))
        {
            throw new UserException('Champ "'.$name.'" : Le type est vide ou non valide.');
        }

        if ($name == 'email' && $config->type != 'email')
        {
            throw new UserException('Le champ email ne peut être d\'un type différent de email.');
        }

        if ($name == 'passe' && $config->type != 'password')
        {
            throw new UserException('Le champ mot de passe ne peut être d\'un type différent de mot de passe.');
        }

        if (($config->type == 'multiple' || $config->type == 'select') && empty($config->options))
        {
            throw new UserException('Le champ "'.$name.'" nécessite de comporter au moins une option possible.');
        }

        if (!property_exists($config, 'editable'))
        {
            $config->editable = false;
        }

        if (!property_exists($config, 'mandatory'))
        {
            $config->mandatory = false;
        }

        if (!property_exists($config, 'private'))
        {
            $config->private = false;
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

        $config = (object) $config;
        
        $this->_checkField($name, $config);

        $this->champs->$name = $config;

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
        if (!isset($this->champs->$champ))
        {
            throw new \LogicException('Champ "'.$champ.'" inconnu.');
        }

        // Vérification
        $config = clone $this->champs->$champ;
        $config->$key = $value;
        $this->_checkField($champ, $config);

		$this->champs->$champ = $config;
		return true;
	}

    public function checkCustomFieldName($name)
    {
        if (in_array($name, $this->system_fields))
        {
            throw new UserException('Ce nom unique de champ existe déjà dans les champs systèmes utilisés par Garradin.');
        }

        $presets = self::importPresets();

        if (array_key_exists($name, $presets))
        {
            throw new UserException('Le champ personnalisé ne peut avoir le même nom qu\'un champ pré-défini.');
        }

        if (isset($this->champs->$name))
        {
            throw new UserException('Ce nom est déjà utilisé par un autre champ.');
        }
    }

    /**
     * Modifie les champs en interne en vérifiant que tout va bien
     * @param array $champs Liste des champs
     * @return boolean true
     */
    public function setAll($champs, $initial_setup = false)
    {
        $presets = self::importPresets();
        $champs = (object) $champs;

        if (!isset($champs->passe))
        {
            $champs->passe = (object) ['type' => 'password'];
        }

        $config = null;

        foreach ($champs as $key=>&$config)
        {
            if (in_array($key, $this->system_fields))
            {
                throw new UserException('Ce nom unique de champ existe déjà dans les champs systèmes utilisés par Garradin.');
            }

            if (is_array($config))
            {
                $config = (object) $config;
            }

            if (isset($presets[$key]))
            {
                $config->type = $presets[$key]['type'];
            }

            $this->_checkField($key, $config);
        }

        unset($config);

        if ($initial_setup)
        {
            $this->champs = $champs;
            return true;
        }

        if (!property_exists($champs, 'email'))
        {
            throw new UserException('Le champ E-Mail ne peut être supprimé des fiches membres.');
        }

        if (!property_exists($champs, 'passe'))
        {
            throw new UserException('Le champ Mot de passe ne peut être supprimé des fiches membres.');
        }

        if (!property_exists($champs, 'numero'))
        {
            throw new UserException('Le champ numéro de membre ne peut être supprimé des fiches membres.');
        }

        $config = Config::getInstance();

        $identite = $config->get('champ_identite');

        if ($identite != 'id' && !property_exists($champs, $identite))
        {
            throw new UserException('Le champ '.$config->get('champ_identite')
                .' est défini comme identité des membres et ne peut donc être supprimé des fiches membres.');
        }

        $identifiant = $config->get('champ_identifiant');

        if ($identifiant != 'id' && !property_exists($champs, $identifiant))
        {
            throw new UserException('Le champ '.$config->get('champ_identifiant')
                .' est défini comme identifiant à la connexion et ne peut donc être supprimé des fiches membres.');
        }

        $this->champs = $champs;

        return true;
    }

    public function getSQLSchema(string $table_name = self::TABLE): string
    {
        $config = Config::getInstance();
        $db = DB::getInstance();

        // Champs à créer
        $create = [
            'id INTEGER PRIMARY KEY, -- Numéro attribué automatiquement',
            'id_category INTEGER NOT NULL REFERENCES users_categories(id),',
            'date_connexion TEXT NULL CHECK (date_connexion IS NULL OR datetime(date_connexion) = date_connexion), -- Date de dernière connexion',
            'date_inscription TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date_inscription) IS NOT NULL AND date(date_inscription) = date_inscription), -- Date d\'inscription',
            'secret_otp TEXT NULL, -- Code secret pour TOTP',
            'clef_pgp TEXT NULL, -- Clé publique PGP'
        ];

        end($this->champs);
        $last_one = key($this->champs);

        foreach ($this->champs as $key=>$cfg)
        {
            if ($cfg->type == 'number' || $cfg->type == 'multiple' || $cfg->type == 'checkbox')
                $type = 'INTEGER';
            elseif ($cfg->type == 'file')
                $type = 'BLOB';
            else
                $type = 'TEXT COLLATE NOCASE';

            $line = sprintf('%s %s', $db->quoteIdentifier($key), $type);

            if ($last_one != $key) {
                $line .= ',';
            }

            if (!empty($cfg->title))
            {
                $line .= ' -- ' . str_replace(["\n", "\r"], '', $cfg->title);
            }

            $create[] = $line;
        }

        $sql = sprintf("CREATE TABLE %s\n(\n\t%s\n);", $table_name, implode("\n\t", $create));
        return $sql;
    }

    public function getCopyFields(): array
    {
        $config = Config::getInstance();

        // Champs à recopier
        $copy = [
            'id'               => 'id',
            'id_category'      => 'id_category',
            'date_connexion'   => 'date_connexion',
            'date_inscription' => 'date_inscription',
            'secret_otp'       => 'secret_otp',
            'clef_pgp'         => 'clef_pgp',
        ];

        $anciens_champs = $config->get('champs_membres');
        $anciens_champs = is_null($anciens_champs) ? $this->champs : $anciens_champs->getAll();

        foreach ($this->champs as $key=>$cfg)
        {
            if (property_exists($anciens_champs, $key)) {
                $copy[$key] = $key;
            }
        }

        return $copy;
    }

    public function getSQLCopy(string $old_table_name, string $new_table_name = self::TABLE, array $fields = null): string
    {
        if (null === $fields) {
            $fields = $this->getCopyFields();
        }

        $db = DB::getInstance();

        return sprintf('INSERT INTO %s (%s) SELECT %s FROM %s;',
            $new_table_name,
            implode(', ', array_map([$db, 'quoteIdentifier'], $fields)),
            implode(', ', array_map([$db, 'quoteIdentifier'], array_keys($fields))),
            $old_table_name
        );
    }

    public function copy(string $old_table_name, string $new_table_name = self::TABLE, array $fields = null): void
    {
        DB::getInstance()->exec($this->getSQLCopy($old_table_name, $new_table_name, $fields));
    }

    public function create(string $table_name = self::TABLE)
    {
        $db = DB::getInstance();
        $db->begin();
        $this->createTable($table_name);
        $this->createIndexes($table_name);
        $db->commit();
    }

    public function createTable(string $table_name = self::TABLE): void
    {
        DB::getInstance()->exec($this->getSQLSchema($table_name));
    }

    public function createIndexes(string $table_name = self::TABLE): void
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

        if ($id_field = $config->get('champ_identifiant')) {
            // Mettre les champs identifiant vides à NULL pour pouvoir créer un index unique
            $db->exec(sprintf('UPDATE %s SET %s = NULL WHERE %2$s = \'\';',
                $table_name, $id_field));

            $collation = '';

            if ($this->isText($id_field)) {
                $collation = ' COLLATE NOCASE';
            }

            // Création de l'index unique
            $db->exec(sprintf('CREATE UNIQUE INDEX IF NOT EXISTS users_id_field ON %s (%s%s);', $table_name, $id_field, $collation));
        }

        $db->exec(sprintf('CREATE UNIQUE INDEX IF NOT EXISTS user_number ON %s (numero);', $table_name));
        $db->exec(sprintf('CREATE INDEX IF NOT EXISTS users_category ON %s (id_category);', $table_name));

        // Create index on listed columns
        // FIXME: these indexes are currently unused by SQLite in the default user list
        // when there is more than one non-hidden category, as this makes SQLite merge multiple results
        // and so the index is not useful in that case sadly.
        // EXPLAIN QUERY PLAN SELECT * FROM membres WHERE "id_category" IN (3) ORDER BY "nom" ASC LIMIT 0,100;
        // --> SEARCH TABLE membres USING INDEX users_list_nom (id_category=?)
        // EXPLAIN QUERY PLAN SELECT * FROM membres WHERE "id_category" IN (3, 7) ORDER BY "nom" ASC LIMIT 0,100;
        // --> SEARCH TABLE membres USING INDEX user_category (id_category=?)
        // USE TEMP B-TREE FOR ORDER BY
        $listed_fields = array_keys((array) $this->getListedFields());
        foreach ($listed_fields as $field) {
            if ($field === $config->get('champ_identifiant')) {
                // Il y a déjà un index
                continue;
            }

            $collation = '';

            if ($this->isText($field)) {
                $collation = ' COLLATE NOCASE';
            }

            $db->exec(sprintf('CREATE INDEX IF NOT EXISTS users_list_%s ON %s (id_category, %1$s%s);', $field, $table_name, $collation));
        }
    }

    /**
     * Enregistre les changements de champs en base de données
     * @return boolean true
     */
    public function save()
    {
        $db = DB::getInstance();
        $config = Config::getInstance();

    	$db->exec('PRAGMA foreign_keys = OFF;');

        $db->begin();
        $this->createTable(self::TABLE . '_tmp');
        $this->copy(self::TABLE, self::TABLE . '_tmp');
        $db->exec(sprintf('DROP TABLE IF EXISTS %s;', self::TABLE));
    	$db->exec(sprintf('ALTER TABLE %s_tmp RENAME TO %1$s;', self::TABLE));

        $this->createIndexes(self::TABLE);

    	$db->commit();
    	$db->exec('PRAGMA foreign_keys = ON;');

    	$config->set('champs_membres', $this);
    	$config->save();

    	return true;
    }
}
