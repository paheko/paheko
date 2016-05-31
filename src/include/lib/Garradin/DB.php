<?php

namespace Garradin;

function str_replace_first ($search, $replace, $subject)
{
    $pos = strpos($subject, $search);

    if ($pos !== false)
    {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }

    return $subject;
}

class DB extends \SQLite3
{
    static protected $_instance = null;

    protected $_transaction = 0;

    const NUM = \SQLITE3_NUM;
    const ASSOC = \SQLITE3_ASSOC;
    const BOTH = \SQLITE3_BOTH;

    static public function getInstance($create = false)
    {
        return self::$_instance ?: self::$_instance = new DB($create);
    }

    private function __clone()
    {
    }

    public function __construct($create = false)
    {
        $flags = SQLITE3_OPEN_READWRITE;

        if ($create)
        {
            $flags |= SQLITE3_OPEN_CREATE;
        }

        parent::__construct(DB_FILE, $flags);

        $this->enableExceptions(true);

        // Le timeout par défaut est 0, on le met à 1 seconde, si ça ne suffit pas on augmentera plus tard
        $this->busyTimeout(1000);

        // Activer les contraintes des foreign keys
        $this->exec('PRAGMA foreign_keys = ON;');

        $this->createFunction('transliterate_to_ascii', ['Garradin\Utils', 'transliterateToAscii']);
        $this->createFunction('base64', 'base64_encode');
        $this->createFunction('rank', [$this, 'sql_rank']);
    }

    public function sql_rank($aMatchInfo)
    {
        $iSize = 4; // byte size
        $iPhrase = (int) 0;                 // Current phrase //
        $score = (double)0.0;               // Value to return //

        /* Check that the number of arguments passed to this function is correct.
        ** If not, jump to wrong_number_args. Set aMatchinfo to point to the array
        ** of unsigned integer values returned by FTS function matchinfo. Set
        ** nPhrase to contain the number of reportable phrases in the users full-text
        ** query, and nCol to the number of columns in the table.
        */
        $aMatchInfo = (string) func_get_arg(0);
        $nPhrase = ord(substr($aMatchInfo, 0, $iSize));
        $nCol = ord(substr($aMatchInfo, $iSize, $iSize));

        if (func_num_args() > (1 + $nCol))
        {
            throw new \Exception("Invalid number of arguments : ".$nCol);
        }

        // Iterate through each phrase in the users query. //
        for ($iPhrase = 0; $iPhrase < $nPhrase; $iPhrase++)
        {
            $iCol = (int) 0; // Current column //

            /* Now iterate through each column in the users query. For each column,
            ** increment the relevancy score by:
            **
            **   (<hit count> / <global hit count>) * <column weight>
            **
            ** aPhraseinfo[] points to the start of the data for phrase iPhrase. So
            ** the hit count and global hit counts for each column are found in
            ** aPhraseinfo[iCol*3] and aPhraseinfo[iCol*3+1], respectively.
            */
            $aPhraseinfo = substr($aMatchInfo, (2 + $iPhrase * $nCol * 3) * $iSize);

            for ($iCol = 0; $iCol < $nCol; $iCol++)
            {
                $nHitCount = ord(substr($aPhraseinfo, 3 * $iCol * $iSize, $iSize));
                $nGlobalHitCount = ord(substr($aPhraseinfo, (3 * $iCol + 1) * $iSize, $iSize));
                $weight = ($iCol < func_num_args() - 1) ? (double) func_get_arg($iCol + 1) : 0;

                if ($nHitCount > 0)
                {
                    $score += ((double)$nHitCount / (double)$nGlobalHitCount) * $weight;
                }
            }
        }

        return $score;
    }

    public function escape($str)
    {
        return $this->escapeString($str);
    }

    public function e($str)
    {
        return $this->escapeString($str);
    }

    public function begin()
    {
        if (!$this->_transaction)
        {
            $this->exec('BEGIN;');
        }

        $this->_transaction++;

        return $this->_transaction == 1 ? true : false;
    }

    public function commit()
    {
        if ($this->_transaction == 1)
        {
            $this->exec('END;');
        }

        if ($this->_transaction > 0)
        {
            $this->_transaction--;
        }

        return $this->_transaction ? false : true;
    }

    public function rollback()
    {
        $this->exec('ROLLBACK;');
        $this->_transaction = 0;
        return true;
    }

    public function getArgType(&$arg, $name = '')
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
            default:
                throw new \InvalidArgumentException('Argument '.$name.' is of invalid type '.gettype($arg));
        }
    }

    public function simpleStatement($query, $args = [])
    {
        $statement = $this->prepare($query);
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
                        throw new \InvalidArgumentException(__FUNCTION__ . ' requires argument to be a named-associative array, but key '.$key.' is an integer.');
                    }

                    $type = $this->getArgType($value, $key);
                    $statement->bindValue(':'.$key, $value, $type);
                }
            }
        }

        try {
            return $statement->execute();
        }
        catch (\Exception $e)
        {
            throw new \Exception($e->getMessage() . "\n" . $query . "\n" . json_encode($args, true));
        }
    }

    public function simpleStatementFetch($query, $mode = SQLITE3_BOTH)
    {
        if ($mode != SQLITE3_BOTH && $mode != SQLITE3_ASSOC && $mode != SQLITE3_NUM)
        {
            throw new \InvalidArgumentException('Mode argument should be either SQLITE3_BOTH, SQLITE3_ASSOC or SQLITE3_NUM.');
        }

        $args = array_slice(func_get_args(), 2);
        return $this->fetchResult($this->simpleStatement($query, $args), $mode);
    }

    public function simpleStatementFetchAssoc($query)
    {
        $args = array_slice(func_get_args(), 1);
        return $this->fetchResultAssoc($this->simpleStatement($query, $args));
    }

    public function simpleStatementFetchAssocKey($query, $mode = SQLITE3_BOTH)
    {
        if ($mode != SQLITE3_BOTH && $mode != SQLITE3_ASSOC && $mode != SQLITE3_NUM)
        {
            throw new \InvalidArgumentException('Mode argument should be either SQLITE3_BOTH, SQLITE3_ASSOC or SQLITE3_NUM.');
        }

        $args = array_slice(func_get_args(), 2);
        return $this->fetchResultAssocKey($this->simpleStatement($query, $args), $mode);
    }

    public function escapeAuto($value, $name = '')
    {
        $type = $this->getArgType($value, $name);

        switch ($type)
        {
            case \SQLITE3_FLOAT:
                return floatval($value);
            case \SQLITE3_INTEGER:
                return intval($value);
            case \SQLITE3_NULL:
                return 'NULL';
            case \SQLITE3_TEXT:
                return '\'' . $this->escapeString($value) . '\'';
        }
    }

    /**
     * Simple INSERT query
     */
    public function simpleInsert($table, $fields)
    {
        $fields_names = array_keys($fields);
        return $this->simpleStatement('INSERT INTO '.$table.' ('.implode(', ', $fields_names).')
            VALUES (:'.implode(', :', $fields_names).');', $fields);
    }

    public function simpleUpdate($table, $fields, $where)
    {
        if (empty($fields))
            return false;
        
        $query = 'UPDATE '.$table.' SET ';

        foreach ($fields as $key=>$value)
        {
            $query .= $key . ' = :'.$key.', ';
        }

        $query = substr($query, 0, -2);
        $query .= ' WHERE '.$where.';';
        return $this->simpleStatement($query, $fields);
    }

    /**
     * Formats and escapes a statement and then returns the result of exec()
     */
    public function simpleExec($query)
    {
        return $this->simpleStatement($query, array_slice(func_get_args(), 1));
    }

    public function simpleQuerySingle($query, $all_columns = false)
    {
        $res = $this->simpleStatement($query, array_slice(func_get_args(), 2));

        $row = $res->fetchArray($all_columns ? SQLITE3_ASSOC : SQLITE3_NUM);

        if (!$all_columns)
        {
            if (isset($row[0]))
                return $row[0];
            return false;
        }
        else
        {
            return $row;
        }
    }

    public function queryFetch($query, $mode = SQLITE3_BOTH)
    {
        return $this->fetchResult($this->query($query), $mode);
    }

    public function queryFetchAssoc($query)
    {
        return $this->fetchResultAssoc($this->query($query));
    }

    public function queryFetchAssocKey($query, $mode = SQLITE3_BOTH)
    {
        return $this->fetchResultAssocKey($this->query($query), $mode);
    }

    public function fetchResult($result, $mode = \SQLITE3_BOTH)
    {
        $out = [];

        while ($row = $result->fetchArray($mode))
        {
            $out[] = $row;
        }

        $result->finalize();
        unset($result, $row);

        return $out;
    }

    protected function fetchResultAssoc($result)
    {
        $out = [];

        while ($row = $result->fetchArray(SQLITE3_NUM))
        {
            $out[$row[0]] = $row[1];
        }

        $result->finalize();
        unset($result, $row);

        return $out;
    }

    protected function fetchResultAssocKey($result, $mode = \SQLITE3_BOTH)
    {
        $out = [];

        while ($row = $result->fetchArray($mode))
        {
            $key = current($row);
            $out[$key] = $row;
        }

        $result->finalize();
        unset($result, $row);

        return $out;
    }

    public function countRows($result)
    {
        $i = 0;

        while ($result->fetchArray(SQLITE3_NUM))
            $i++;

        return $i;
    }
}

?>