<?php

function str_replace_first ($search, $replace, $subject)
{
    $pos = strpos($subject, $search);

    if ($pos !== false)
    {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }

    return $subject;
}

define('SQLITE3_INSTRUCTION', 42);

class SQLite3_Instruction
{
    protected $instruction = '';

    public function __construct($instruction)
    {
        $this->instruction = $instruction;
    }

    public function __toString()
    {
        return $this->instruction;
    }
}

class Garradin_DB extends SQLite3
{
    static protected $_instance = null;

    protected $_running_sum = 0.0;

    static public function getInstance()
    {
        return self::$_instance ?: self::$_instance = new Garradin_DB;
    }

    private function __clone()
    {
    }

    public function __construct()
    {
        $exists = file_exists(GARRADIN_DB_FILE) ? true : false;

        parent::__construct(GARRADIN_DB_FILE, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);

        // Activer les contraintes des foreign keys
        $this->exec('PRAGMA foreign_keys = ON;');

        if (!$exists)
        {
            $this->exec('BEGIN;');
            $this->exec(file_get_contents(GARRADIN_DB_SCHEMA));
            $this->exec('END;');
        }

        $this->createFunction('transliterate_to_ascii', array('utils', 'transliterateToAscii'));
        $this->createFunction('base64', 'base64_encode');
        $this->createFunction('rank', array($this, 'sql_rank'));
        $this->createFunction('running_sum', array($this, 'sql_running_sum'));
    }

    public function sql_running_sum($data)
    {
        // Why is this function called two times for the first row?!
        // Dunno but here is a workaround
        if (is_null($this->_running_sum))
        {
            $this->_running_sum = 0.0;
            return $this->_running_sum;
        }

        $this->_running_sum += $data;
        return $this->_running_sum;
    }

    public function resetRunningSum()
    {
        $this->_running_sum = null;
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
            throw new Exception("Invalid number of arguments : ".$nCol);
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

    protected function _getArgType($arg, $name = '')
    {
        if (is_float($arg))
            return SQLITE3_FLOAT;
        elseif (is_int($arg))
            return SQLITE3_INTEGER;
        elseif (is_bool($arg))
            return SQLITE3_INTEGER;
        elseif (is_null($arg))
            return SQLITE3_NULL;
        elseif (is_string($arg))
            return SQLITE3_TEXT;
        elseif (is_object($arg) && $arg instanceof SQLite3_instruction)
            return SQLITE3_INSTRUCTION;
        else
            throw new InvalidArgumentException('Argument '.$name.' is of invalid type '.gettype($arg));
    }

    public function simpleStatement($query, $args = array())
    {
        $statement = $this->prepare($query);
        $nb = $statement->paramCount();

        if (count($args) == 1 && is_array($args[0]))
        {
            if (count($args[0]) != $nb)
            {
                throw new LengthException('Only '.count($args[0]).' arguments in array, but '.$nb.' are required by query.');
            }

            foreach ($args[0] as $key=>$value)
            {
                if (is_int($key))
                {
                    throw new InvalidArgumentException(__FUNCTION__ . ' requires second argument to be a named-associative array, but key '.$key.' is an integer.');
                }

                $statement->bindValue(':'.$key, $value, $this->_getArgType($value, $key));
            }
        }
        else
        {
            if (count($args) != $nb)
            {
                throw new LengthException('Only '.count($args).' arguments, but '.$nb.' are required by query.');
            }

            for ($i = 1; $i <= count($args); $i++)
            {
                $arg = $args[$i - 1];
                $statement->bindValue($i, $arg, $this->_getArgType($arg, $i));
            }
        }

        return $statement->execute();
    }

    public function simpleStatementFetch($query, $mode = SQLITE3_BOTH)
    {
        if ($mode != SQLITE3_BOTH && $mode != SQLITE3_ASSOC && $mode != SQLITE3_NUM)
        {
            throw new InvalidArgumentException('Mode argument should be either SQLITE3_BOTH, SQLITE3_ASSOC or SQLITE3_NUM.');
        }

        $args = array_slice(func_get_args(), 2);
        return $this->_fetchResult($this->simpleStatement($query, $args), $mode);
    }

    public function simpleStatementFetchAssoc($query)
    {
        $args = array_slice(func_get_args(), 1);
        return $this->_fetchResultAssoc($this->simpleStatement($query, $args));
    }

    public function simpleStatementFetchAssocKey($query, $mode = SQLITE3_BOTH)
    {
        if ($mode != SQLITE3_BOTH && $mode != SQLITE3_ASSOC && $mode != SQLITE3_NUM)
        {
            throw new InvalidArgumentException('Mode argument should be either SQLITE3_BOTH, SQLITE3_ASSOC or SQLITE3_NUM.');
        }

        $args = array_slice(func_get_args(), 2);
        return $this->_fetchResultAssocKey($this->simpleStatement($query, $args), $mode);
    }

    public function escapeAuto($value, $name = '')
    {
        $type = $this->_getArgType($value, $name);

        switch ($type)
        {
            case SQLITE3_FLOAT:
                return floatval($value);
            case SQLITE3_INTEGER:
                return intval($value);
            case SQLITE3_NULL:
                return 'NULL';
            case SQLITE3_TEXT:
                return '\'' . $this->escapeString($value) . '\'';
            case SQLITE3_INSTRUCTION:
                return (string) $value;
        }
    }

    /**
     * Returns a correct, escaped query from a query statement and list of arguments,
     * either as named array or as a list of indexed arguments.
     */
    protected function _getSimpleQuery($query, $args)
    {
        if (count($args) == 1 && is_array($args[0]))
        {
            preg_match_all('/:[a-z_]+/', $query, $matches);
            $nb = count(array_unique($matches[0]));

            if (count($args[0]) < $nb)
            {
                throw new LengthException('Only '.count($args[0]).' arguments in array, but '.$nb.' are required by query.');
            }

            foreach ($args[0] as $key=>$value)
            {
                if (is_int($key))
                {
                    throw new InvalidArgumentException(__FUNCTION__ . ' requires second argument to be a named-associative array, but key '.$key.' is an integer.');
                }

                $value = preg_replace('#(?<!\\\\)(\\$|\\\\)#', '\\\\$1', $this->escapeAuto($value, $key));
                $query = preg_replace('/:'.$key.'(?![a-z])/', $value, $query);
            }
        }
        else
        {
            $nb = substr_count($query, '?');

            if (count($args) != $nb)
            {
                throw new LengthException('Only '.count($args).' arguments, but '.$nb.' are required by query.');
            }

            for ($i = 1; $i <= count($args); $i++)
            {
                $arg = $args[$i - 1];
                $arg = $this->escapeAuto($arg, $i);

                $pos = strpos($query, '?');
                $query = substr_replace($query, $arg, $pos, 1);
            }
        }

        return $query;
    }

    /**
     * Simple INSERT query
     */
    public function simpleInsert($table, $fields)
    {
        $fields_names = array_keys($fields);
        return $this->simpleExec('INSERT INTO '.$table.' ('.implode(', ', $fields_names).')
            VALUES (:'.implode(', :', $fields_names).');', $fields);
    }

    public function simpleUpdate($table, $fields, $where)
    {
        $query = 'UPDATE '.$table.' SET ';

        foreach ($fields as $key=>$value)
        {
            $query .= $key . ' = :'.$key.', ';
        }

        $query = substr($query, 0, -2);
        $query .= ' WHERE '.$where.';';
        return $this->simpleExec($query, $fields);
    }

    /**
     * Formats and escapes a statement and then returns the result of exec()
     */
    public function simpleExec($query)
    {
        $args = array_slice(func_get_args(), 1);
        $query = $this->_getSimpleQuery($query, $args);

        try {
            return $this->exec($query);
        }
        catch (ErrorException $e)
        {
            echo $query;
            echo "\n\n";
            throw $e;
        }
    }

    public function simpleQuerySingle($query, $all_columns = false)
    {
        $args = array_slice(func_get_args(), 2);
        $query = $this->_getSimpleQuery($query, $args);

        try {
            return $this->querySingle($query, $all_columns);
        }
        catch (ErrorException $e)
        {
            echo $query;
            echo "\n\n";
            throw $e;
        }
    }

    public function queryFetch($query, $mode = SQLITE3_BOTH)
    {
        return $this->_fetchResult($this->query($query), $mode);
    }

    public function queryFetchAssoc($query)
    {
        return $this->_fetchResultAssoc($this->query($query));
    }

    public function queryFetchAssocKey($query, $mode = SQLITE3_BOTH)
    {
        return $this->_fetchResultAssocKey($this->query($query), $mode);
    }

    protected function _fetchResult($result, $mode)
    {
        $out = array();

        while ($row = $result->fetchArray($mode))
        {
            $out[] = $row;
        }

        $result->finalize();
        unset($result, $row);

        return $out;
    }

    protected function _fetchResultAssoc($result)
    {
        $out = array();

        while ($row = $result->fetchArray(SQLITE3_NUM))
        {
            $out[$row[0]] = $row[1];
        }

        $result->finalize();
        unset($result, $row);

        return $out;
    }

    protected function _fetchResultAssocKey($result, $mode)
    {
        $out = array();

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