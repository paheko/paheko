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

    public function query($query, Array $args = [])
    {
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

    public function get($query)
    {
        $args = array_slice(func_get_args(), 1);
        return $this->fetch($this->query($query, $args), self::OBJ);
    }

    public function getAssoc($query)
    {
        $args = array_slice(func_get_args(), 1);
        return $this->fetchAssoc($this->query($query, $args));
    }

    public function getAssocKey($query)
    {
        $args = array_slice(func_get_args(), 1);
        return $this->fetchAssocKey($this->query($query, $args), self::OBJ);
    }

    public function insert($table, Array $fields)
    {
        $fields_names = array_keys($fields);
        $query = sprintf('INSERT INTO %s (%s) VALUES (:%s);', $table, 
            implode(', ', $fields_names), implode(', :', $fields_names));

        return $this->query($query, $fields);
    }

    public function update($table, Array $fields, $where)
    {
        // No fields to update? no need to do a query
        if (empty($fields))
        {
            return false;
        }

        $args = array_slice(func_get_args(), 3);
        $column_updates = [];
        
        foreach ($fields as $key=>$value)
        {
            // Append to arguments
            $args['field_' . $key] = $value;

            $column_updates[] = sprintf('%s = :field_%s', $key, $key);
        }

        $column_updates = implode(', ', $column_updates);
        $query = sprintf('UPDATE %s SET %s WHERE %s;', $table, $column_updates, $where);

        return $this->query($query, $args);
    }

    public function delete($table, $where)
    {
        $query = sprintf('DELETE FROM %s WHERE %s;', $table, $where);
        return $this->query($query, array_slice(func_get_args(), 2));
    }

    public function exec($query)
    {
        return $this->query($query, array_slice(func_get_args(), 1));
    }

    public function first($query)
    {
        $res = $this->query($query, array_slice(func_get_args(), 1));

        $row = $res->fetchArray(SQLITE3_ASSOC);
        $res->finalize();

        return is_array($row) ? (object) $row : false;
    }

    public function firstColumn($query)
    {
        $res = $this->query($query, array_slice(func_get_args(), 1));

        $row = $res->fetchArray(\SQLITE3_NUM);

        return count($row) > 0 ? $row[0] : false;
    }

    public function fetch(\SQLite3Result $result, $mode = null)
    {
        $as_obj = false;

        if ($mode === self::OBJ)
        {
            $as_obj = true;
            $mode = self::ASSOC;
        }

        $out = [];

        while ($row = $result->fetchArray($mode))
        {
            // FIXME: use generator (PHP 5.6+)
            $out[] = $as_obj ? (object) $row : $row;
        }

        $result->finalize();
        unset($result, $row);

        return $out;
    }

    protected function fetchAssoc(\SQLite3Result $result)
    {
        $out = [];

        while ($row = $result->fetchArray(\SQLITE3_NUM))
        {
            // FIXME: use generator
            $out[$row[0]] = $row[1];
        }

        $result->finalize();
        unset($result, $row);

        return $out;
    }

    protected function fetchAssocKey(\SQLite3Result $result, $mode = null)
    {
        $as_obj = false;

        if ($mode === self::OBJ)
        {
            $as_obj = true;
            $mode = self::ASSOC;
        }

        $out = [];

        while ($row = $result->fetchArray($mode))
        {
            // FIXME: use generator (PHP 5.6+)
            $key = current($row);
            $out[$key] = $as_obj ? (object) $row : $row;
        }

        $result->finalize();
        unset($result, $row);

        return $out;
    }

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
