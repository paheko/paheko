<?php

namespace Garradin;

class DB
{
    static protected $_instance = null;

    /**
     * Instance SQLite3
     * @var SQLite3
     */
    protected $db = null;

    /**
     * Options d'initialisation de SQLite3
     * @var null
     */
    protected $flags = null;

    /**
     * Transaction en cours?
     * @var integer
     */
    protected $transaction = 0;

    /**
     * Modes de retour des résultats
     */
    const NUM = \SQLITE3_NUM;
    const ASSOC = \SQLITE3_ASSOC;
    const BOTH = \SQLITE3_BOTH;
    const OBJ = 4; // SQLITE3_ASSOC, NUM and BOTH are 1, 2 and 3, so let's start at 4

    /**
     * Format de date utilisé pour le stockage
     */
    const DATE_FORMAT = 'Y-m-d H:i:s';

    static public function getInstance($create = false)
    {
        return self::$_instance ?: self::$_instance = new DB($create);
    }

    private function __clone()
    {
        // Désactiver le clonage, car on ne veut qu'une seule instance
    }

    public function __construct($create = false)
    {
        $this->flags = \SQLITE3_OPEN_READWRITE;

        if ($create)
        {
            $this->flags |= \SQLITE3_OPEN_CREATE;
        }

        // Ne pas se connecter ici, on ne se connectera que quand une requête sera faite
    }

    public function connect()
    {
        if ($this->db)
        {
            return true;
        }

        $this->db = new \SQLite3(DB_FILE, $this->flags);

        $this->db->enableExceptions(true);

        // Le timeout par défaut est 0, on le met à 1 seconde, si ça ne suffit pas on augmentera plus tard
        $this->db->busyTimeout(1000);

        // Activer les contraintes des foreign keys
        $this->db->exec('PRAGMA foreign_keys = ON;');

        $this->db->createFunction('transliterate_to_ascii', ['Garradin\Utils', 'transliterateToAscii']);
        $this->db->createFunction('base64', 'base64_encode');
        $this->db->createFunction('rank', ['\KD2\DB', 'sqlite_rank']);
        $this->db->createFunction('haversine_distance', ['\KD2\DB', 'sqlite_haversine']);
    }

    public function escape($str)
    {
        // escapeString n'est pas binary safe: https://bugs.php.net/bug.php?id=62361
        $str = str_replace("\0", "\\0", $str);

        $this->connect();
        return $this->db->escapeString($str);
    }

    public function quote($str)
    {
        return '\'' . $this->escape($str) . '\'';
    }

    public function begin()
    {
        if (!$this->transaction)
        {
            $this->connect();
            $this->db->exec('BEGIN;');
        }

        $this->transaction++;

        return $this->transaction == 1 ? true : false;
    }

    public function commit()
    {
        if ($this->transaction == 1)
        {
            $this->connect();
            $this->db->exec('END;');
        }

        if ($this->transaction > 0)
        {
            $this->transaction--;
        }

        return $this->transaction ? false : true;
    }

    public function rollback()
    {
        $this->connect();
        $this->db->exec('ROLLBACK;');
        $this->transaction = 0;
        return true;
    }

    protected function getArgType(&$arg, $name = '')
    {
        switch (gettype($arg))
        {
            case 'double':
                return \SQLITE3_FLOAT;
            case 'integer':
            case 'boolean':
                return \SQLITE3_INTEGER;
            case 'NULL':
                return \SQLITE3_NULL;
            case 'string':
                return \SQLITE3_TEXT;
            case 'array':
                if (count($arg) == 2 
                    && in_array($arg[0], [\SQLITE3_FLOAT, \SQLITE3_INTEGER, \SQLITE3_NULL, \SQLITE3_TEXT, \SQLITE3_BLOB]))
                {
                    $type = $arg[0];
                    $arg = $arg[1];

                    return $type;
                }
            case 'object':
                if ($arg instanceof \DateTime)
                {
                    $arg = $arg->format(self::DATE_FORMAT);
                    return \SQLITE3_TEXT;
                }
            default:
                throw new \InvalidArgumentException('Argument '.$name.' is of invalid type '.gettype($arg));
        }
    }

    /**
     * Performe une requête en utilisant les arguments contenus dans le tableau $args
     * @param  string       $query Requête SQL
     * @param  array|object $args  Arguments à utiliser comme bindings pour la requête
     * @return \SQLite3Statement|boolean Retourne un booléen si c'est une requête 
     * qui exécute une opération d'écriture, ou un statement si c'est une requête de lecture.
     *
     * Note: le fait que cette fonction retourne un booléen est un comportement
     * volontaire pour éviter un bug dans le module SQLite3 de PHP, qui provoque
     * un risque de faire des opérations en double en cas d'exécution de 
     * ->fetchResult() sur un statement d'écriture.
     */
    public function query($query, Array $args = [])
    {
        assert(is_string($query));
        assert(is_array($args) || is_object($args));
        
        // Forcer en tableau
        $args = (array) $args;

        $this->connect();
        $statement = $this->db->prepare($query);
        $nb = $statement->paramCount();

        if (!empty($args))
        {
            if (is_array($args) && count($args) == 1 && is_array(current($args)))
            {
                $args = current($args);
            }
            
            if (count($args) != $nb)
            {
                throw new \LengthException('Arguments error: '.count($args).' supplied, but '.$nb.' are required by query.');
            }

            reset($args);

            if (is_int(key($args)))
            {
                foreach ($args as $i=>$arg)
                {
                    if (is_string($i))
                    {
                        throw new \InvalidArgumentException(sprintf('%s requires argument to be a keyed array, but key %s is a string.', __FUNCTION__, $i));
                    }

                    $type = $this->getArgType($arg, $i+1);
                    $statement->bindValue((int)$i+1, $arg, $type);
                }
            }
            else
            {
                foreach ($args as $key=>$value)
                {
                    if (is_int($key))
                    {
                        throw new \InvalidArgumentException(sprintf('%s requires argument to be a named-associative array, but key %s is an integer.', __FUNCTION__, $key));
                    }

                    $type = $this->getArgType($value, $key);
                    $statement->bindValue(':'.$key, $value, $type);
                }
            }
        }

        try {
            // Return a boolean for write queries to avoid accidental duplicate execution
            // see https://bugs.php.net/bug.php?id=64531
            
            $result = $statement->execute();
            return $statement->readOnly() ? $result : (bool) $result;
        }
        catch (\Exception $e)
        {
            throw new \RuntimeException($e->getMessage() . "\n" . $query . "\n" . json_encode($args, true));
        }
    }

    /**
     * Exécute une requête et retourne le résultat sous forme de tableau
     * @param  string $query Requête SQL
     * @return array Tableau contenant des objets
     *
     * Accepte un ou plusieurs arguments supplémentaires utilisés comme bindings
     * pour la clause WHERE.
     */
    public function get($query)
    {
        $args = array_slice(func_get_args(), 1);
        
        $out = [];

        foreach ($this->fetch($this->query($query, $args), self::OBJ) as $key=>$row)
        {
            $out[$key] = $row;
        }

        return $out;
    }

    /**
     * Exécute une requête et retourne le résultat sous forme de tableau associatif
     * en utilisant les deux premières colonnes retournées,
     * de la forme [colonne1 => colonne2, colonne1 => colonne2, ...]
     * @param  string $query Requête SQL
     * @return array Tableau associatif
     *
     * Accepte un ou plusieurs arguments supplémentaires utilisés comme bindings
     * pour la clause WHERE.
     */
    public function getAssoc($query)
    {
        $args = array_slice(func_get_args(), 1);

        $out = [];
        
        foreach ($this->fetchAssoc($this->query($query, $args)) as $key=>$row)
        {
            $out[$key] = $row;
        }

        return $out;
    }

    /**
     * Exécute une requête et retourne le résultat sous forme de tableau associatif
     * en utilisant la première colonne comme clé:
     * [colonne1 => (object) [colonne1 => valeur1, colonne2 => valeur2, ...], ...]
     * @param  string $query Requête SQL
     * @return array Tableau associatif contenant des objets
     *
     * Accepte un ou plusieurs arguments supplémentaires utilisés comme bindings
     * pour la clause WHERE.
     */
    public function getAssocKey($query)
    {
        $args = array_slice(func_get_args(), 1);

        $out = [];

        foreach ($this->fetchAssocKey($this->query($query, $args), self::OBJ) as $key=>$row)
        {
            $out[$key] = $row;
        }

        return $out;
    }

    /**
     * Insère une ligne dans la table $table, en remplissant avec les champs donnés
     * dans $fields (tableau associatif ou objet)
     * @param  string $table  Table où insérer
     * @param  string $fields Champs à remplir
     * @return boolean
     */
    public function insert($table, $fields)
    {
        assert(is_array($fields) || is_object($fields));

        $fields = (array) $fields;

        $fields_names = array_keys($fields);
        $query = sprintf('INSERT INTO %s (%s) VALUES (:%s);', $table, 
            implode(', ', $fields_names), implode(', :', $fields_names));

        return $this->query($query, $fields);
    }

    /**
     * Met à jour une ou plusieurs lignes de la table
     * @param  string       $table  Nom de la table
     * @param  array|object $fields Liste des champs à mettre à jour
     * @param  string       $where  Clause WHERE
     * @param  array|object $args   Arguments pour la clause WHERE
     * @return boolean
     */
    public function update($table, $fields, $where, $args = [])
    {
        assert(is_string($table));
        assert(is_string($where) && strlen($where));
        assert(is_array($fields) || is_object($fields));
        assert(is_array($args) || is_object($args));

        // Forcer en tableau
        $fields = (array) $fields;
        $args = (array) $args;

        // No fields to update? no need to do a query
        if (empty($fields))
        {
            return false;
        }

        $column_updates = [];
        
        foreach ($fields as $key=>$value)
        {
            // Append to arguments
            $args['field_' . $key] = $value;

            $column_updates[] = sprintf('%s = :field_%s', $key, $key);
        }

        // Assemblage de la requête
        $column_updates = implode(', ', $column_updates);
        $query = sprintf('UPDATE %s SET %s WHERE %s;', $table, $column_updates, $where);

        return $this->query($query, $args);
    }

    /**
     * Supprime une ou plusieurs lignes d'une table
     * @param  string $table Nom de la table
     * @param  string $where Clause WHERE
     * @return boolean
     *
     * Accepte un ou plusieurs arguments supplémentaires utilisés comme bindings
     * pour la clause WHERE.
     */
    public function delete($table, $where)
    {
        $query = sprintf('DELETE FROM %s WHERE %s;', $table, $where);
        return $this->query($query, array_slice(func_get_args(), 2));
    }

    /**
     * Exécute une requête SQL (alias pour query)
     * @param  string $query Requête SQL
     * @return boolean
     *
     * Accepte un ou plusieurs arguments supplémentaires utilisés comme bindings.
     */
    public function exec($query)
    {
        return $this->query($query, array_slice(func_get_args(), 1));
    }

    /**
     * Exécute une requête et retourne la première ligne
     * @param  string $query Requête SQL
     * @return object
     *
     * Accepte un ou plusieurs arguments supplémentaires utilisés comme bindings.
     */
    public function first($query)
    {
        $res = $this->query($query, array_slice(func_get_args(), 1));

        $row = $res->fetchArray(SQLITE3_ASSOC);
        $res->finalize();

        return is_array($row) ? (object) $row : false;
    }

    /**
     * Exécute une requête et retourne la première colonne de la première ligne
     * @param  string $query Requête SQL
     * @return object
     *
     * Accepte un ou plusieurs arguments supplémentaires utilisés comme bindings.
     */
    public function firstColumn($query)
    {
        $res = $this->query($query, array_slice(func_get_args(), 1));

        $row = $res->fetchArray(\SQLITE3_NUM);

        return count($row) > 0 ? $row[0] : false;
    }

    /**
     * Récupère le résultat d'un statement
     * @param  \SQLite3Result $result Résultat de statement
     * @param  integer        $mode   Mode de récupération (BOTH, OBJ, NUM ou ASSOC)
     * @return array
     */
    public function fetch(\SQLite3Result $result, $mode = null)
    {
        $as_obj = false;

        if ($mode === self::OBJ)
        {
            $as_obj = true;
            $mode = self::ASSOC;
        }

        while ($row = $result->fetchArray($mode))
        {
            yield ($as_obj ? (object) $row : $row);
        }

        $result->finalize();
        unset($result, $row);

        return;
    }

    /**
     * Récupère le résultat d'un statement sous forme de tableau associatif
     * avec colonne1 comme clé et colonne2 comme valeur.
     * 
     * @param  \SQLite3Result $result Résultat de statement
     * @param  integer        $mode   Mode de récupération (BOTH, OBJ, NUM ou ASSOC)
     * @return array
     */
    protected function fetchAssoc(\SQLite3Result $result)
    {
        while ($row = $result->fetchArray(\SQLITE3_NUM))
        {
            yield $row[0] => $row[1];
        }

        $result->finalize();
        unset($result, $row);

        return;
    }

    /**
     * Récupère le résultat d'un statement sous forme de tableau associatif 
     * avec colonne1 comme clé et la ligne comme valeur.
     * @param  \SQLite3Result $result Résultat de statement
     * @param  integer        $mode   Mode de récupération (BOTH, OBJ, NUM ou ASSOC)
     * @return array
     */
    protected function fetchAssocKey(\SQLite3Result $result, $mode = null)
    {
        $as_obj = false;

        if ($mode === self::OBJ)
        {
            $as_obj = true;
            $mode = self::ASSOC;
        }

        while ($row = $result->fetchArray($mode))
        {
            $key = current($row);
            yield $key => ($as_obj ? (object) $row : $row);
        }

        $result->finalize();
        unset($result, $row, $key);

        return;
    }

    /**
     * Compte le nombre de lignes dans un résultat
     * @param  \SQLite3Result $result Résultat SQLite3
     * @return integer
     */
    public function countRows(\SQLite3Result $result)
    {
        $i = 0;

        while ($result->fetchArray(\SQLITE3_NUM))
        {
            $i++;
        }

        $result->reset();

        return $i;
    }

    /**
     * Préparer un statement SQLite3
     * @param  string $query Requête SQL
     * @return \SQLite3Statement
     */
    public function prepare($query)
    {
        return $this->db->prepare($query);
    }

    /**
     * @deprecated
     */
    public function simpleInsert($table, Array $fields)
    {
        return $this->insert($table, $fields);
    }

    /**
     * @deprecated
     */
    public function simpleUpdate($table, Array $fields, $where)
    {
        return $this->update($table, $fields, $where);
    }

    /**
     * @deprecated
     */
    public function simpleExec($query)
    {
        return $this->simpleStatement($query, array_slice(func_get_args(), 1));
    }

    /**
     * @deprecated
     */
    public function escapeString($str)
    {
        return $this->escape($str);
    }

    /**
     * @deprecated
     */
    public function simpleStatement($query, Array $args = [])
    {
        return $this->statement($query, $args);
    }

    /**
     * @deprecated
     */
    public function simpleStatementFetch($query, $mode = null)
    {
        $args = array_slice(func_get_args(), 2);
        return $this->fetch($this->query($query, $args), $mode);
    }

    /**
     * @deprecated
     */
    public function simpleStatementFetchAssoc($query)
    {
        $args = array_slice(func_get_args(), 1);
        return $this->fetchAssoc($this->query($query, $args));
    }

    /**
     * @deprecated
     */
    public function simpleStatementFetchAssocKey($query, $mode = null)
    {
        $args = array_slice(func_get_args(), 2);
        return $this->fetchAssocKey($this->query($query, $args), $mode);
    }

    /**
     * @deprecated
     */
    public function queryFetch($query, $mode = null)
    {
        return $this->fetch($this->query($query), $mode);
    }

    /**
     * @deprecated
     */
    public function queryFetchAssoc($query)
    {
        return $this->fetchAssoc($this->query($query));
    }

    /**
     * @deprecated
     */
    public function queryFetchAssocKey($query, $mode = null)
    {
        return $this->fetchAssocKey($this->query($query), $mode);
    }

    /**
     * @deprecated
     */
    public function simpleQuerySingle($query, $all_columns = false)
    {
        $res = $this->query($query, array_slice(func_get_args(), 2));

        $row = $res->fetchArray($all_columns ? SQLITE3_ASSOC : SQLITE3_NUM);
        $res->finalize();

        if (!$all_columns)
        {
            if (isset($row[0]))
            {
                return $row[0];
            }

            return false;
        }
        else
        {
            return $row;
        }
    }
}
