<?php

class Garradin_DB extends SQLite3
{
    protected $db = null;

    static protected $_instance = null;

    static public function getInstance()
    {
        return self::$_instance ?: self::$_instance = new Garradin_DB;
    }

    private function __clone()
    {
    }

    protected function __construct()
    {
        $exists = file_exists(GARRADIN_DB_FILE) ? true : false;

        $this->db = parent::construct(GARRADIN_DB_FILE);

        if (!$exists)
        {
            $this->db->exec('BEGIN;');
            $this->db->exec(file_get_contents(GARRADIN_DB_SCHEMA));
            $this->db->exec('END;');
        }
    }

    public function escape($str)
    {
        return $this->escapeString($str);
    }

    public function e($str)
    {
        return $this->escapeString($str);
    }

    public function simpleStatement($query)
    {
        $statement = $this->prepare($query);

        for ($i = 2; $i <= func_num_args(); $i++)
        {
            $arg = func_get_arg($i - 1);

            if (is_float($arg)) $type = SQLITE3_FLOAT;
            elseif (is_numeric($arg)) $type = SQLITE3_INTEGER;
            elseif (is_bool($arg)) $type = SQLITE3_INTEGER;
            elseif (is_null($arg)) $type = SQLITE3_NULL;
            else $type = SQLITE3_TEXT;

            $statement->bindValue($i - 1, $arg, $type);
        }

        return $statement->execute();
    }

    public function simpleStatementFetch($query, $mode = SQLITE3_BOTH)
    {
        return $this->_fetchResult($this->simpleStatement($query), $mode);
    }

    public function queryFetch($query, $mode = SQLITE3_BOTH)
    {
        return $this->_fetchResult($this->query($query));
    }

    public function queryFetchAssoc($query)
    {
        return $this->_fetchResultAssoc($this->query($query));
    }

    protected function _fetchResult($result, $mode)
    {
        $out = array();

        while ($row = $result->fetchArray($mode))
        {
            $out[] = $row;
        }

        $res->finalize();
        unset($res, $row);

        return $out;
    }

    protected function _fetchResultAssoc($result)
    {
        $out = array();

        while ($row = $result->fetchArray(SQLITE3_NUM))
        {
            $out[$row[0]] = $row[1];
        }

        $res->finalize();
        unset($res, $row);

        return $out;
    }

    public function __destruct()
    {
        $this->db->close();
    }
}

?>